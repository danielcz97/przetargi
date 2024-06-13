<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\Post;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ClaimController extends Controller
{
    public function index($slug)
    {
        $today = Carbon::today();
        $mediaUrlBase = env('MEDIA_URL', 'https://przetargi-gctrader.pl');

        $property = Claim::where('slug', $slug)->firstOrFail();
        $mainMedia = $property->getFirstMedia('default');
        $mainMediaUrl = $mainMedia ? $mediaUrlBase . $mainMedia->getUrl() : null;

        $galleryMedia = $property->getMedia('default')->reject(function ($media) use ($mainMedia) {
            return $media->id === $mainMedia->id;
        })->map(function ($media) use ($mediaUrlBase) {
            $media->url = $mediaUrlBase . $media->getUrl();
            return $media;
        });

        $properties = Claim::whereDate('created', '<=', $today)
            ->orderBy('created', 'desc')
            ->paginate(15);
        $properties->each(function ($property) use ($mediaUrlBase) {
            $property->mainMediaUrl = $mediaUrlBase . $property->getFirstMediaUrl('default');
        });

        $comunicats = Post::whereDate('created', '<=', $today)
            ->orderBy('created', 'desc')
            ->paginate(5);
        $comunicats->each(function ($comunicat) use ($mediaUrlBase) {
            $comunicat->mainMediaUrl = $mediaUrlBase . $comunicat->getFirstMediaUrl('default');
        });

        $createdDate = Carbon::parse($property->created);
        $formattedDateNumeric = $createdDate->format('d/m/Y');
        $formattedDateText = $createdDate->translatedFormat('j F Y');
        $maps = 'https://maps.googleapis.com/maps/api/js?key=' . env('GOOGLE_MAPS_API_KEY') . '&libraries=places';

        return view(
            'node.claim',
            compact(
                'property',
                'properties',
                'comunicats',
                'formattedDateNumeric',
                'formattedDateText',
                'mainMediaUrl',
                'galleryMedia',
                'maps'
            )
        );
    }


    public function printPage($slug)
    {
        $property = Claim::where('slug', $slug)->firstOrFail();
        $createdDate = Carbon::parse($property->created);
        $formattedDateNumeric = $createdDate->format('d/m/Y');
        $formattedDateText = $createdDate->translatedFormat('j F Y');
        $fullLocation = $property->getFullLocation();
        $transactionDetails = $property->getTransactionDetails();

        PDF::setOptions(['defaultFont' => 'DejaVu Sans']);

        $pdf = PDF::loadView('print', compact('property', 'formattedDateNumeric', 'formattedDateText', 'fullLocation', 'transactionDetails'));

        return $pdf->stream('property.pdf');
    }
    public function getTransactionDetails()
    {
        return [];
    }
}