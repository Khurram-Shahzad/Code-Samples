<?php

namespace App\Services;

class ListingService
{
    private string $collectionName = 'mls-photos';
    private RetsService $retsService;

    public function __construct(RetsService $retsService)
    {
        $this->retsService = $retsService;
    }

    public function import($retsQuery = null, $listingHistory = null): void
    {
        $listingHistory ? $this->importFromFile($listingHistory) : $this->importFromRets($retsQuery);
    }

    public function populateGpsCoordinates($limit = 50): void
    {
        Helper::setListingCoordinates($limit);
        if (Listing::where('gps_check', false)->count()) {
            $this->populateGpsCoordinates($limit);
        }
    }

    public function populateMappingData($locations): void
    {
        $data = [];
        foreach ($locations as $location) {
            $data[] = [
                'id' => $location->id,
                'latitude' => $location->lat,
                'longitude' => $location->lng,
            ];
        }

        $response = Http::timeout(60)
            ->post(
                config('communication.url').':'.config('communication.mapping_port').'/api/v1/mapping-data/',
                $data
            );

        if ($response->successful()) {
            $results = json_decode($response->body());

            foreach ($results as $result) {
                GpsLocations::where('id', $result->id)->update([
                    'is_usda_eligible' => $result->is_usda_eligible,
                    'is_opportunity_zone' => $result->is_opportunity_zone,
                    'zones' => json_encode($result->zones),
                ]);
            }
        }
    }
}
