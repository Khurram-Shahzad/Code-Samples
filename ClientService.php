<?php


namespace App\Services;

class ClientService
{
    public function getClients($searchFor = null)
    {
        return Client::query()
            ->with([
                'user',
                'homeDesign.preApproval.sbmpResults' => function ($query) {
                    $query
                        ->select('pre_approval_id', 'is_copied', DB::raw('count(DISTINCT ListingId) as total'))
                        ->groupBy('pre_approval_id', 'is_copied');
                },
                'preApprovalRequest.user.detail',
                'contact',
                'timers' => function ($query) {
                    $query->latest();
                }
            ])
            ->where('user_id', auth()->id())
            ->tap(function ($query) use ($searchFor) {
                if ($searchFor) {
                    $searchFor = urldecode($searchFor);
                    $query->where(function ($query) use ($searchFor) {
                        $query->orWhere('first_name', 'LIKE', '%'.$searchFor.'%')
                            ->orWhere('last_name', 'LIKE', '%'.$searchFor.'%')
                            ->orWhere('email', 'LIKE', '%'.$searchFor.'%')
                            ->orWhere('phone', 'LIKE', '%'.$searchFor.'%')
                            ->orWhere('notes', 'LIKE', '%'.$searchFor.'%');
                    });
                }
            })
            ->orderBy('active', 'DESC')
            ->latest()
            ->paginate(20);
    }

    public function checkIfUserIsAuthenticatable($email)
    {
        if ($this->userAlreadyExists($email)) {
            return true;
        }
        return $this->checkIfUserIsInvited($email);
    }
}
