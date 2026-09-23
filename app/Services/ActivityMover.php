<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Models\Event;
use App\Models\Gift;
use App\Models\Project;

/**
 * Converts an "activity" record (project / gift / event) into another type,
 * carrying its receipts and expenditures across and migrating member
 * contributions between gifts and events. Project milestones and dues are
 * dropped on conversion (receipts are kept and re-pointed). All in one
 * transaction.
 */
final class ActivityMover
{
    public const TYPES = ['project', 'gift', 'event'];

    /** url segment => table */
    private const TABLE = ['project' => 'projects', 'gift' => 'gifts', 'event' => 'events'];
    /** the link column on receipts/expenditures */
    private const LINK = ['project' => 'project_id', 'gift' => 'gift_id', 'event' => 'event_id'];

    private Database $db;

    public function __construct()
    {
        $this->db = Database::instance();
    }

    public static function label(string $type): string
    {
        return ['project' => 'Project', 'gift' => 'Gift', 'event' => 'Event'][$type] ?? $type;
    }

    /** Load a source record scoped to the association, or null. */
    public function find(string $type, int $id, int $assocId): ?array
    {
        if (!in_array($type, self::TYPES, true)) {
            return null;
        }
        return $this->db->fetch(
            'SELECT * FROM ' . self::TABLE[$type] . ' WHERE id = ? AND association_id = ?',
            [$id, $assocId]
        );
    }

    /**
     * A summary of what a conversion would carry over / drop, for the review step.
     * @return array<string,mixed>
     */
    public function impact(string $from, int $id, int $assocId): array
    {
        $link = self::LINK[$from];
        $receipts = $this->db->fetch(
            "SELECT COUNT(*) c, COALESCE(SUM(amount),0) s FROM receipts WHERE association_id = ? AND {$link} = ?",
            [$assocId, $id]
        );
        $exp = $this->db->fetch(
            "SELECT COUNT(*) c, COALESCE(SUM(amount),0) s FROM expenditures WHERE association_id = ? AND {$link} = ?",
            [$assocId, $id]
        );
        $out = [
            'receipts_count'  => (int) ($receipts['c'] ?? 0),
            'receipts_sum'    => (float) ($receipts['s'] ?? 0),
            'exp_count'       => (int) ($exp['c'] ?? 0),
            'exp_sum'         => (float) ($exp['s'] ?? 0),
            'contrib_count'   => 0,
            'milestones'      => 0,
            'dues'            => 0,
        ];
        if ($from === 'gift') {
            $out['contrib_count'] = (int) $this->db->fetchColumn('SELECT COUNT(*) FROM gift_members WHERE gift_id = ?', [$id]);
        } elseif ($from === 'event') {
            $out['contrib_count'] = (int) $this->db->fetchColumn('SELECT COUNT(*) FROM event_members WHERE event_id = ?', [$id]);
        } elseif ($from === 'project') {
            $out['milestones'] = (int) $this->db->fetchColumn('SELECT COUNT(*) FROM project_milestones WHERE project_id = ?', [$id]);
            $out['dues'] = (int) $this->db->fetchColumn('SELECT COUNT(*) FROM demands WHERE project_id = ? AND association_id = ?', [$id, $assocId]);
        }
        return $out;
    }

    /**
     * Perform the conversion. Returns the new record id.
     * @param array<string,mixed> $extra  type_id (target master), direction (gift)
     */
    public function move(string $from, int $id, string $to, array $extra, int $assocId): int
    {
        $src = $this->find($from, $id, $assocId);
        if ($src === null) {
            throw new \RuntimeException('Source record not found.');
        }
        if ($from === $to || !in_array($to, self::TYPES, true)) {
            throw new \RuntimeException('Invalid destination type.');
        }

        return (int) $this->db->transaction(function () use ($from, $id, $to, $src, $extra, $assocId) {
            $data = $this->mapFields($from, $to, $src, $extra, $assocId);
            $newId = match ($to) {
                'project' => (new Project())->create($data),
                'gift'    => (new Gift())->create($data),
                'event'   => (new Event())->create($data),
            };

            $fromLink = self::LINK[$from];
            $pid = $to === 'project' ? $newId : null;
            $gid = $to === 'gift' ? $newId : null;
            $eid = $to === 'event' ? $newId : null;

            // Re-point receipts (clearing any due link — gifts/events have no dues).
            $rcptCat = $to; // receipts.category enum uses project/gift/event
            $this->db->run(
                "UPDATE receipts SET category = ?, project_id = ?, gift_id = ?, event_id = ?, demand_id = NULL
                 WHERE association_id = ? AND {$fromLink} = ?",
                [$rcptCat, $pid, $gid, $eid, $assocId, $id]
            );
            // Re-point expenditures.
            $this->db->run(
                "UPDATE expenditures SET category = ?, project_id = ?, gift_id = ?, event_id = ?
                 WHERE association_id = ? AND {$fromLink} = ?",
                [$to, $pid, $gid, $eid, $assocId, $id]
            );

            // Migrate member contributions between gift and event.
            if ($from === 'gift' && $to === 'event') {
                $this->db->run(
                    'INSERT INTO event_members (association_id, event_id, member_id, contribution)
                     SELECT association_id, ?, member_id, contribution FROM gift_members WHERE gift_id = ?',
                    [$newId, $id]
                );
            } elseif ($from === 'event' && $to === 'gift') {
                $this->db->run(
                    'INSERT INTO gift_members (association_id, gift_id, member_id, contribution)
                     SELECT association_id, ?, member_id, contribution FROM event_members WHERE event_id = ?',
                    [$newId, $id]
                );
            }

            // Delete the source record's satellites.
            if ($from === 'project') {
                // Drop milestones (and their photos) and dues.
                $photos = $this->db->fetchAll('SELECT photo_path FROM project_milestones WHERE project_id = ? AND photo_path IS NOT NULL', [$id]);
                $uploader = new ImageUploader();
                foreach ($photos as $p) {
                    $uploader->delete($p['photo_path'] ?? null);
                }
                $this->db->run('DELETE FROM project_milestones WHERE project_id = ?', [$id]);
                $this->db->run('DELETE FROM demands WHERE project_id = ? AND association_id = ?', [$id, $assocId]);
            } elseif ($from === 'gift') {
                $this->db->run('DELETE FROM gift_members WHERE gift_id = ?', [$id]);
            } elseif ($from === 'event') {
                $this->db->run('DELETE FROM event_members WHERE event_id = ?', [$id]);
            }

            // Remove the source record.
            $this->db->run('DELETE FROM ' . self::TABLE[$from] . ' WHERE id = ? AND association_id = ?', [$id, $assocId]);

            return $newId;
        });
    }

    /** @return array<string,mixed> */
    private function mapFields(string $from, string $to, array $src, array $extra, int $assocId): array
    {
        $name = (string) ($src['name'] ?? $src['title'] ?? 'Untitled');
        $money = (float) match ($from) {
            'project' => $src['target_amount'] ?? 0,
            default   => $src['value'] ?? 0,
        };
        $desc = $src['description'] ?? null;
        $start = $from === 'gift' ? ($src['gift_date'] ?? null) : ($src['start_date'] ?? null);
        $end = $from === 'gift' ? null : ($src['end_date'] ?? null);
        $typeId = ($extra['type_id'] ?? null) ?: null;
        $defaultContribution = $src['default_contribution'] ?? null; // gift/event only
        $base = ['association_id' => $assocId, 'created_by' => Auth::id()];

        return match ($to) {
            'project' => $base + [
                'project_type_id' => $typeId,
                'name'            => $name,
                'description'     => $desc,
                'status'          => $this->mapStatus((string) ($src['status'] ?? 'active'), 'project'),
                'target_amount'   => $money,
                'start_date'      => $start,
                'end_date'        => $end,
            ],
            'gift' => $base + [
                'gift_type_id'         => $typeId,
                'direction'            => in_array($extra['direction'] ?? '', ['in', 'out'], true) ? $extra['direction'] : 'in',
                'title'                => $name,
                'party'                => null,
                'member_id'            => null,
                'value'                => $money,
                'default_contribution' => $defaultContribution,
                'gift_date'            => $start,
                'description'          => $desc,
            ],
            'event' => $base + [
                'event_type_id'      => $typeId,
                'title'              => $name,
                'venue'              => null,
                'location'           => null,
                'start_date'         => $start,
                'end_date'           => $end,
                'registration_start' => null,
                'registration_end'   => null,
                'status'             => $this->mapStatus((string) ($src['status'] ?? 'planned'), 'event'),
                'value'              => $money,
                'default_contribution' => $defaultContribution,
                'description'        => $desc,
            ],
        };
    }

    private function mapStatus(string $status, string $to): string
    {
        if ($to === 'project') {
            $allowed = ['planned', 'active', 'completed', 'on_hold', 'cancelled'];
            return in_array($status, $allowed, true) ? $status : 'active';
        }
        // event: planned / completed / cancelled
        return match ($status) {
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            default     => 'planned',
        };
    }
}
