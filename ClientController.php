<?php

namespace App\Http\Controllers\Client;

class ClientController extends Controller
{
    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    public function index($searchFor = null)
    {
        $clients = $this->clientService->getClients($searchFor);
        $phoneNumbers = $this->clientService->getAllClientsPhoneNumbers($clients);
        return view(
            'clients.list',
            compact('clients', 'phoneNumbers', 'searchFor')
        );
    }

    public function searchClient()
    {
        return $this->clientService->searchClients(request('searchFor'));
    }

    public function triggerManualResultRefresh()
    {
        $request = json_decode(request()->getContent());
        $clientUUid = $request->clientUuid;
        $client = Client::with('homeDesign', 'homeDesign.preApproval')->where('uuid', $clientUUid)->first();
        if (! $client) {
            return 'Invalid client uuid.';
        }

        if ($client->homeDesign->preApproval) {
            PopulateHomeDesignResults::dispatch
                $client->homeDesign, $client->homeDesign->id, auth()->id()
            )->onQueue('high');
            return 'Triggered refresh queue.';
        }

        return 'PreApproval not found.';
    }
}
