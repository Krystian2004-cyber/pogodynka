<?php

namespace App\Controller;

use App\Entity\Measurement;
use App\Service\WeatherUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class WeatherApiController extends AbstractController
{
    #[Route('/api/v1/weather', name: 'app_weather_api', methods: ['GET'])]
    public function index(
        WeatherUtil $weatherUtil,
        #[MapQueryParameter] string $city,
        #[MapQueryParameter] string $country,
        #[MapQueryParameter] string $format = 'json',
        #[MapQueryParameter('twig')] bool $twig = false,
    ): Response {
        if (!in_array($format, ['json', 'csv'], true)) {
            return new Response('Nieprawidłowy format. Dozwolone: json, csv.', Response::HTTP_BAD_REQUEST);
        }

        $measurements = $weatherUtil->getWeatherForCountryAndCity($country, $city);

        if ($twig) {
            $response = $this->render('weather_api/index.' . $format . '.twig', [
                'city' => $city,
                'country' => $country,
                'measurements' => $measurements,
            ]);
            $response->headers->set(
                'Content-Type',
                $format === 'csv' ? 'text/plain; charset=utf-8' : 'application/json',
            );

            return $response;
        }

        if ($format === 'csv') {
            $lines = ['city,country,date,celsius,fahrenheit'];
            foreach ($measurements as $m) {
                $lines[] = sprintf(
                    '%s,%s,%s,%s,%s',
                    $city,
                    $country,
                    $m->getDate()->format('Y-m-d'),
                    $m->getCelsius(),
                    $m->getFahrenheit(),
                );
            }

            return new Response(implode("\n", $lines) . "\n", Response::HTTP_OK, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        return $this->json([
            'city' => $city,
            'country' => $country,
            'measurements' => array_map(fn(Measurement $m) => [
                'date' => $m->getDate()->format('Y-m-d'),
                'celsius' => $m->getCelsius(),
                'fahrenheit' => $m->getFahrenheit(),
            ], $measurements),
        ]);
    }
}
