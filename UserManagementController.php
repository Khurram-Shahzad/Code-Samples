<?php

namespace App\Http\Controllers\User;

class UserManagementController extends Controller
{
    protected $userService;
    public function __construct(UserService $userSevice)
    {
        $this->userService = $userSevice;
    }
    
    public function authenticate($userUuid)
    {
        if (! auth()->user()->hasRole('admin')) {
            abort(403, 'You are not allowed to access this resource.');
        }
        $user = User::where('uuid', $userUuid)->firstOrFail();
        $this->userService->logoutCurrentUser();
        auth()->login($user);
        return redirect()->route('home');
    }

    public function getUserList()
    {
        $registeredUsers = $this->userService->getRegisteredUsers();
        $invitedUsers = $this->userService->getInvitedUsers();
        $registeredAndInvitedUsersPhoneNumber = $this->userService->getRegisteredAndInvitedUsersPhoneNumber(
            $registeredUsers,
            $invitedUsers
        );

        return view('user.list', [
            'userList' =>  $registeredUsers,
            'invites' =>  $invitedUsers,
            'allPhoneNumbers' => $registeredAndInvitedUsersPhoneNumber,
        ]);
    }

    public function createNewInvite($userTypeIdentifier = null, $userTypeId = null)
    {
        $record = $this->userService->getEmptyRecord();

        if ($userTypeIdentifier == 'client') {
            $record = $this->userService->getClientData($userTypeId);
        } elseif ($userTypeIdentifier == 'contact') {
            $record = $this->userService->getContactData($userTypeIdentifier, $userTypeId);
        } elseif (in_array($userTypeIdentifier, ['admin', 'home-buyer', 'agent', 'loan-officer'])) {
            $record = $this->userService->getInviteData($userTypeId);
        }


        $roles = Roles::all();
        $countryCodes = Country::select('phonecode')->distinct()->orderby('phonecode', 'ASC')->get();
        $phoneNumbers = $this->userService->getUserPhoneNumbers();

        return view(
            'user.create',
            compact('roles', 'countryCodes', 'phoneNumbers', 'record')
        );
    }

    public function registerInvitedUser(Request $request)
    {
        $request->validate([
            'inviteToken' => ['required'],
            'password' => ['required', 'confirmed']
        ]);
        if (! request('termsOfUse')) {
            return back()->withErrors('Please accept terms of use to continue');
        }
        $invite = Invite::where('token', request('inviteToken'))->firstOrFail();
        return $this->userService->createUserFromInvite($invite, request('password'));
    }
}
