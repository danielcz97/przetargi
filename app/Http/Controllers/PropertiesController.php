<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Post;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Class PropertiesController
 * @package App\Http\Controllers
 */
class PropertiesController extends Controller
{
    public function index($slug)
    {
        $today = Carbon::today();
        $mediaUrlBase = env('MEDIA_URL', 'https://przetargi-gctrader.pl');

        $property = Property::where('slug', $slug)->firstOrFail();

        $mainMedia = $property->getFirstMedia('default');
        $mainMediaUrl = $mainMedia ? $mediaUrlBase . $mainMedia->getUrl() : null;

        $galleryMedia = $property->getMedia('default')->reject(function ($media) use ($mainMedia) {
            return $media->id === $mainMedia->id;
        })->map(function ($media) use ($mediaUrlBase) {
            $media->url = $mediaUrlBase . $media->getUrl();
            return $media;
        });

        $properties = Property::whereDate('created', '<=', $today)
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

        return view('node.index', compact('property', 'properties', 'comunicats', 'formattedDateNumeric', 'formattedDateText', 'mainMediaUrl', 'galleryMedia', 'maps'));
    }


    public function printPage($slug)
    {
        $property = Property::where('slug', $slug)->firstOrFail();
        $createdDate = Carbon::parse($property->created);
        $formattedDateNumeric = $createdDate->format('d/m/Y');
        $formattedDateText = $createdDate->translatedFormat('j F Y');
        $fullLocation = $property->getFullLocation();
        $transactionDetails = $property->getTransactionDetails();

        PDF::setOptions(['defaultFont' => 'DejaVu Sans']);

        $pdf = PDF::loadView('print', compact('property', 'formattedDateNumeric', 'formattedDateText', 'fullLocation', 'transactionDetails'));

        return $pdf->stream('property.pdf');
    }

    private function updateImagePaths($body)
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if (strpos($src, 'http') === false) {
                $img->setAttribute('src', url($src));
            }
        }

        return $dom->saveHTML();
    }

}