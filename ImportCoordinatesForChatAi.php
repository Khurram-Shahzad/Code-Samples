<?php

namespace App\Jobs;

class ImportCoordinatesForChatAi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $contacts;

    public function __construct($contacts)
    {
        $this->contacts = $contacts;
    }

    public function handle(GpsService $gpsService, ListingService $listingService)
    {
        foreach ($this->contacts as $contact) {
            $propertyAddress = $contact->property_address ?? '';
            if (! $propertyAddress) {
                continue;
            }
            $coordinates = $gpsService->getGpsCoordinatesFromGoogle($propertyAddress);
            $contact->is_gps_coordinates_fetched = true;
            $contact->save();
            $contact->coordinates()->updateOrCreate([
                'lat' => $coordinates['lat'],
                'lng' => $coordinates['lng'],
                'address' => $propertyAddress,
                'key' => md5($propertyAddress),
                'coordinates_source' => 'google'
            ]);
        }

        $gpsLocations = GpsLocations::whereNull('zones')->get();
        $listingService->populateMappingData($gpsLocations);
    }
}
