<?php 

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GetFromSirene
{
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function fetchInfo($sirenNumber): array
    {
        $response = $this->client->request(
            'GET',
            // on acolle à l'url le Siren fourni en argument
            'http://entreprise.data.gouv.fr/api/sirene/v3/etablissements/?etat_administratif=A&longiture&latitude&siren='.$sirenNumber,            
            [
                // these values are automatically encoded before including them in the URL
                'query' => 
                [
                    // je fourni ici le token qui a été généré lors de l'inscription à l'API
                    'token' => 'a51dba3d-d798-31dc-9e77-661069c42d43',
                ]
            ]);

        // on récupère le code HTTP
           $statusCode = $response->getStatusCode();
        // si c'est un code 200, ça signifie que l'api à trouvé une correspondance avec le numéro de SIREN
        if ($statusCode == 200) 
        {                      
            $location = $response->getContent();
            // on récupère alors le résultat de la requête et on le met dans un tableau
            $location = $response->toArray();
            // on renvoi le tout en réponse
            return $location;       
        }
        // si ce n'est pas un code 200, on stop tout et on renvoi un tableau vide
        else
        {
            return [];
        }
    }
}