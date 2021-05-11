<?php

namespace App\Console\Commands;

use App\Jobs\ImportCoordinatesForChatAi;
use App\Models\Contact;
use Illuminate\Console\Command;

class ImportCoordinatesForChatAiCommand extends Command
{
    protected $signature = 'chatai:coordinates';

    protected $description = 'Import GPS coordinates for Chat AI Contacts';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle() : void
    {
        $contacts = Contact::query()
            ->where('group_name', 'ChatAI')
            ->where('is_gps_coordinates_fetched', 0)
            ->get();
        if ($contacts->count()) {
            ImportCoordinatesForChatAi::dispatch($contacts);
            $this->info($contacts->count() . ' records pushed to queue');
            return;
        }
        $this->info('No new chat ai contacts found');
    }
}
