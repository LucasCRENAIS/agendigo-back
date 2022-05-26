<?php 

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GetFromUnsplash
{
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function fetch($cityName): string
    {
        $response = $this->client->request(
            'GET',
            // on acolle à l'url le nom de la ville fourni en argument
            'https://api.unsplash.com/search/photos?page=1&per_page=1&query=city-'.$cityName.'&orientation=landscape',            
            [
                // these values are automatically encoded before including them in the URL
                'headers' => 
                [
                    // je fourni ici le token qui a été généré lors de l'inscription à l'API
                    'Authorization' => 'Client-ID ghy-8wa6Xp9pERtYRL5s3zH86lLIbC4K5ytTlVLZxCY',
                ]
            ]);

        // on récupère le code HTTP
           $statusCode = $response->getStatusCode();
        // si c'est un code 200, ça signifie que l'api nous a retourné un résultat
        if ($statusCode == 200) 
        {        
            // on récupére le contenu              
            $picture = $response->getContent();
            // on renvoi le tout en réponse
            return  $picture;       
        }
        // si ce n'est pas un code 200, on stop tout et on renvoi null
        else
        {
            return null;
        }
    }
}