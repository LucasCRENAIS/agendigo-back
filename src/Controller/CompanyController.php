<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Company;
use App\Entity\Day;
use App\Entity\TimeSlot;
use App\Repository\CompanyRepository;
use App\Repository\DayRepository;
use App\Repository\ServiceRepository;
use App\Repository\TimeSlotRepository;
use App\Service\AvailableTimeSlot;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\GetFromSirene;
use App\Service\GetFromUnsplash;

class CompanyController extends AbstractController
{
    /**
     * @Route("/api/v1/cities/{city}/activities/{activity}/list", name="api_v1_companies_list", methods={"GET"}, requirements={"city"="^[A-Za-zàáâãäåçèéêëìíîïðòóôõöùúûüýÿ]+$", "activity"="^[A-Za-zàáâãäåçèéêëìíîïðòóôõöùúûüýÿ]+$"})
     */
    public function listCompaniesByActivity(string $city, string $activity, CompanyRepository $companyRepository): Response
    {
		$list = $companyRepository->findCompaniesInActivity($city, $activity);
		return $this->json($list, 200);
    }

	/**
     * @Route("/api/v1/cities", name="api_v1_cities_list", methods={"GET"})
     */
    public function listCities(CompanyRepository $companyRepository, GetFromUnsplash $getFromUnsplash): Response
    {
		$list = $companyRepository->findCities();

		// pour chaque nom de ville
		for ($i = 0; $i < count($list); $i++) 
		{
		// on le récupère dans une variable
			$currentCity = $list[$i]['city'];
		// si il est différent de null
			if ($currentCity != NULL) 
			{
			// on le passe à la fonction $getFromUnsplash qui va récupérer une image en prenant
			// en paramètre le nom de la ville récupéré
			// on récupère ensuite le champ qui nou interesse (->results[0]->urls->small)
				$cities = json_decode($getFromUnsplash->fetch($currentCity))->results[0]->urls->small;
			// on ajoute cette image à l'entrée "image" de notre tableau list pour qu'il apparaissent sous le nom de chaque ville
				$list[$i]['image'] = $cities;
			}							
		};
		//on renvoi le tout
		return $this->json($list, 200);
    }

	/**
     * @Route("/api/v1/company/{id}/services/{serviceId}", name="api_v1_company_planning", methods="GET", requirements={"id"="\d+", "serviceId"="\d+"})
     */
	public function getAvailableTimeSlots(Company $company, Int $serviceId, AvailableTimeSlot $availableTimeSlot)
    {
		$planning = $availableTimeSlot->listAvailableTimeSlots($company, $serviceId);
		
		return $this->json($planning, 200);
    }

    /**
     * @Route("/api/v1/company/{id}/services", name="api_v1_company_read", methods="GET", requirements={"id"="\d+"})
     */
    public function read(Company $company, GetFromSirene $getFromSirene)
    {
      $companyDetails = [
          "company" => $company,
		  "category" => $company->getCategory()->getName(),
		  "activity" => $company->getActivity()->getName(),
          "services" => $company->getServices(),
		  "days" => $company->getDays(),
          "ratings" => $company->getRatings(),
          ];

      // si le uméro de SIREN est défini en BDD
      if (($company->getSirenNumber() != null)) 
		{
			// on apelle la methode fetchInfo du service getFromSirene 
			// et on lui fourni le SIREN de la compagnie en argument
			$sireneApi = $getFromSirene->fetchInfo($company->getSirenNumber());
			// si la réponse n'est pas un tableau vide (donc si l'API SIREN trouve une correspondance)
			if ($sireneApi !=[]) 
				{
					// on récupère les éléments latitude et longitude qui nous interessent
					//! au cas où, on les transforme en float, à modifier si ça pose problème
					$latitude = (float)$sireneApi["etablissements"][0]["unite_legale"]["etablissement_siege"]["latitude"];
					$longitude = (float)$sireneApi["etablissements"][0]["unite_legale"]["etablissement_siege"]["longitude"];
					// on les ajoute au tableau companyDetails à la clé "location"
					$companyDetails["location"] = [$latitude . ", " .  $longitude];
				}
			// sinon, on stock un message d'erreur à la clé "locationError" à la place des coordonnées
			else
				{
                    $companyDetails["locationError"] = "Attention, le SIREN fourni est invalide";
                }
		}
       // dans tout les cas on retourne le tout dans un tableau	
       return $this->json([
          $companyDetails,
	   ], 200);
       //! en principe le résultat de la clé location est utilisable tel quel par l'API de geomapping, à voir lors des tests
     }

    /**
     *  @Route ("/api/v1/dashboard/company/{id}", name="api_v1_company_dashboard", methods="GET", requirements={"id"="\d+"})
     */
    public function CompanyAppointments(CompanyRepository $companyRepository, $id, Company $company)
    {
        return $this->json([
            "company_name" => $company->getCompanyName(),
            // requête custom qui ne retourne que les rendez-vous résérvés
            "appointments" => $companyRepository->getAppointementDetails($id)
		], 200);
    }  
	
	/**
     *  @Route ("/api/v1/dashboard/company/{id}/profile/update", name="api_v1_company_profile_update", methods="PATCH", requirements={"id"="\d+"})
     */
    public function updateProfile(Request $request, Company $company, EntityManagerInterface $em, ValidatorInterface $validator, DayRepository $dayRepository)
    {
        // Decode le fichier JSON est retourne un tableau
		$data = json_decode($request->getContent(), true);

		$updatedDays = [];

		for($i = 1; $i < count($data); $i++)
		{
			// Je récupère l'heure d'ouverture du matin
			// La fonction date_create_from_format me permet de convertir l'heure en objet du format DateTime qui est attendu par l'entité DAY
			$amOpening = $data[$i]["am_opening"];

			// Je récupère l'heure de fermeture du matin
			// Si une valeur est fournie alors je la stocke dans une variable
			if ($data[$i]["am_closing"] !== null)
			{
				$amClosing  = $data[$i]["am_closing"];
			}

			// Je récupère l'heure d'ouverture de l'après-midi
			// Si une valeur est fournie alors je la stocke dans une variable
			if ($data[$i]["pm_opening"] !== null)
			{
				$pmOpening  = $data[$i]["pm_opening"];
			}
			
			// Je récupère de fermeture de l'après-midi
			$pmClosing = $data[$i]["pm_closing"];

			// Ici je récupère le jour pour lequel la mise à jour doit être effectuée
			$day= $dayRepository->find($data[$i]["id"]);

			// On définie l'heure d'ouverture du jour
			$day->setAmOpening($amOpening);

			// Si aucune heure de fermeture du matin n'est fournie alors on définie la valeur comme null pour la db
			// Sinon on définie l'heure récupérée
			if ($data[$i]["am_closing"] == null)
			{
				$day->setAmClosing(null);
			}
			else
			{
				$day->setAmClosing($amClosing);
			}

			// Ici c'est le même principe que pour l'heure de fermeture du matin mais pour l'heure d'ouverture de l'après-midi
			if ($data[$i]["pm_opening"] == null)
			{
				$day->setPmOpening(null);
			}
			else
			{
				$day->setPmOpening($pmOpening);
			}
			
			// On définie l'heure de fermeture de l'après-midi
			$day->setPmClosing($pmClosing);

			// On vérifie les contraintes de validation (Voir dans l'entité Day.php)
			$errors = $validator->validate($day);
			
			// S'il y a une erreur et qu'au moins une contrainte n'est pas respectée
			// On retourne l'erreur sous forme de chaîne de caractère
			if (count($errors) > 0) {
				$errorsString = (string) $errors;
				
				return new Response($errorsString);
			}
			
			array_push($updatedDays, $day);
		}

		// On vérifie si des informations sont renseignées pour chaque ligne du tableau $data
		// Si oui, mise à jour en DB
		// Si non, on ne fait rien
		if(isset($data[0]["company_name"])) {
			$company->setCompanyName($data[0]["company_name"]);
		}

		if(isset($data[0]["siren_number"])) {
			$company->setSirenNumber($data[0]["siren_number"]);
		}

		if(isset($data[0]["firstname"])) {
			$company->setFirstname($data[0]["firstname"]);
		}

		if(isset($data[0]["lastname"])) {
			$company->setLastname($data[0]["lastname"]);
		}

		if(isset($data[0]["email"])) {
			$company->setEmail($data[0]["email"]);
		}

		if(isset($data[0]["description"])) {
			$company->setDescription($data[0]["description"]);
		}

		if(isset($data[0]["image"])) {
			$company->setImage($data[0]["image"]);
		}

		if(isset($data[0]["phone_number"])) {
			$company->setPhoneNumber($data[0]["phone_number"]);
		}

		if(isset($data[0]["address"])) {
			$company->setAddress($data[0]["address"]);
		}

		if(isset($data[0]["postcode"])) {
			$company->setPostcode($data[0]["postcode"]);
		}

		if(isset($data[0]["city"])) {
			$company->setCity($data[0]["city"]);
		}
		
		// On vérifie les contraintes de validation (Voir dans l'entité Company.php)
		$errors = $validator->validate($company);
		
		// S'il y a une erreur et qu'au moins une contrainte n'est pas respectée
		// On retourne l'erreur sous forme de chaîne de caractère
		if (count($errors) > 0) {
			$errorsString = (string) $errors;
			
			return new Response($errorsString);
		}

		// puis on exécute la mise à jour en DB
		$em->flush();
		
		// On retourne la company mise à jour et le code 201 qui correspond au succès de l'opération
        return $this->json([
			"company_details" => $company, 
			"days" => $updatedDays
		], 201);
    }

	/**
     *  @Route ("/api/v1/dashboard/company/{id}/profile", name="api_v1_company_profile_read", methods="GET", requirements={"id"="\d+"})
     */
    public function readProfile(Company $company)
    {
		return $this->json([
			"company" => $company,
			"days" => $company->getDays()
		], 200);
    }
}
