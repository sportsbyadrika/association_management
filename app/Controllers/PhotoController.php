<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Association;
use App\Models\Member;
use App\Models\ProjectMilestone;
use App\Services\ImageUploader;

/**
 * Serves uploaded images from outside the web root, gated by authorization so
 * one association can never read another's member/project photos.
 */
final class PhotoController extends Controller
{
    public function show(Request $request, array $params): void
    {
        $type = $params['type'] ?? '';
        $id = (int) ($params['id'] ?? 0);

        $relative = $this->resolve($type, $id);
        if ($relative === null) {
            Response::notFound('Image not found.');
        }

        $base = (new ImageUploader())->baseDir();
        $path = $base . '/' . ltrim($relative, '/');
        $real = realpath($path);
        if ($real === false || !str_starts_with($real, realpath($base) ?: $base) || !is_file($real)) {
            Response::notFound('Image not found.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($real) ?: 'application/octet-stream';
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            Response::forbidden();
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($real));
        header('Cache-Control: private, max-age=3600');
        readfile($real);
        exit;
    }

    /**
     * Returns the stored relative photo path if the current user may view it.
     */
    private function resolve(string $type, int $id): ?string
    {
        $assocId = Auth::associationId();

        if ($type === 'association') {
            $assoc = (new Association())->find($id);
            if ($assoc === null) {
                return null;
            }
            // Super admin sees all; others only their own association logo.
            if (!Auth::isSuperAdmin() && $assocId !== (int) $assoc['id']) {
                return null;
            }
            return $assoc['logo_path'] ?: null;
        }

        if ($assocId === null) {
            return null;
        }

        if ($type === 'member') {
            $member = (new Member())->findForAssociation($id, $assocId);
            // Members may view their own photo.
            if ($member === null) {
                return null;
            }
            if (Auth::role() === 'member' && (int) (Auth::user()['member_id'] ?? 0) !== $id) {
                return null;
            }
            return $member['photo_path'] ?: null;
        }

        if ($type === 'milestone') {
            $milestone = (new ProjectMilestone())->find($id);
            if ($milestone === null) {
                return null;
            }
            // Verify the milestone's project belongs to this association.
            $ok = (int) $milestone['project_id'] > 0 && (int) $this->belongsToAssociation((int) $milestone['project_id'], $assocId) === 1;
            return $ok ? ($milestone['photo_path'] ?: null) : null;
        }

        return null;
    }

    private function belongsToAssociation(int $projectId, int $assocId): int
    {
        return (int) (new ProjectMilestone())->db()->fetchColumn(
            'SELECT COUNT(*) FROM projects WHERE id = ? AND association_id = ?',
            [$projectId, $assocId]
        );
    }
}
