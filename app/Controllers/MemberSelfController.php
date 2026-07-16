<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Member;
use App\Services\MemberLedger;

/**
 * Member self-service: read-only profile + own ledger, scoped strictly to the
 * member's own record.
 */
final class MemberSelfController extends Controller
{
    public function profile(Request $request): void
    {
        $member = $this->currentMember();
        $this->view('member_self.profile', [
            'title'  => 'My Profile',
            'member' => $member,
        ]);
    }

    public function ledger(Request $request): void
    {
        $member = $this->currentMember();
        $ledger = (new MemberLedger())->build((int) $member['id']);
        $this->view('member_self.ledger', [
            'title'  => 'My Ledger',
            'member' => $member,
            'ledger' => $ledger,
        ]);
    }

    /** @return array<string,mixed> */
    private function currentMember(): array
    {
        $user = Auth::user();
        $memberId = (int) ($user['member_id'] ?? 0);
        $assocId = (int) ($user['association_id'] ?? 0);
        $member = $memberId > 0 ? (new Member())->findWithType($memberId, $assocId) : null;
        if ($member === null) {
            Response::notFound('No member record is linked to your account. Please contact your association admin.');
        }
        return $member;
    }
}
