<?php

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ServiceController extends AbstractController
{
	/**
     * @Route("/api/v1/dashboard/company/{id}/services/create", name="api_v1_company_services_create", methods={"POST"}, requirements={"id"="\d+"})
     */
    public function create(Company $company, Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
		// Ici je récupère le contenu de la requête envoyée depuis le Front
		// C'est grâce à l'objet Request disponible en argument que j'accède à ce contenu
		// Nous recevons les informations au format JSON, nous devons donc les décoder pour les obtenir sous forme un tableau
		// Nous stockons les informations sous forme de tableau dans la variable $data 
		$data = json_decode($request->getContent(), true);

		// Il faut instancier la classe Service afin de configurer le nouveau service qui va être ajouté en base de donnée
		$service = new Service();

		// On vérifie si des informations sont renseignées pour chaque ligne du tableau
		// Si oui, on ajoute l'information en base de données
		if(isset($data["name"])) {
			$service->setName($data["name"]);
		}

		if(isset($data["description"])) {
			$service->setDescription($data["description"]);
		}

		if(isset($data["duration"])) {
			$service->setDuration($data["duration"]);
		}

		if(isset($data["price"])) {
			$service->setPrice($data["price"]);
		}

		// Ici on associe le service que nous sommes en train de créer à la company qui créé ce service
		$service->setCompany($company);

		// Ici on définit la date de création du service
		$service->setCreatedAt(new \DateTime);

		// On vérifie les contraintes de validation
		$errors = $validator->validate($service);

		// S'il y a une erreur et qu'au moins une contrainte n'est pas respectée
		// On retourne l'erreur sous forme de chaîne de caractère
		if (count($errors) > 0) {
			$errorsString = (string) $errors;

			return new Response($errorsString);
		}

		// On persiste les modifications puis on exécute la mise à jour en DB
		$em->persist($service);
		$em->flush();

        return $this->json($service, 201);
    }

	/**
     * @Route("/api/v1/dashboard/company/services/{id}/update", name="api_v1_company_services_update", methods={"PATCH"}, requirements={"id"="\d+"})
     */
    public function update(Request $request, Service $service, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
		// Decode le fichier JSON est retourne un tableau
		$data = json_decode($request->getContent(), true);

		// On vérifie si des informations sont renseignées pour chaque ligne du tableau $data
		// Si oui, mise à jour en DB
		// Si non, on ne fait rien
		if(isset($data["name"])) {
			$service->setName($data["name"]);
		}

		if(isset($data["description"])) {
			$service->setDescription($data["description"]);
		}

		if(isset($data["duration"])) {
			$service->setDuration($data["duration"]);
		}

		if(isset($data["price"])) {
			$service->setPrice($data["price"]);
		}
		
		// On vérifie les contraintes de validation (Voir dans l'entité Service.php => @Assert\NotBlank au-dessus des propriétés Name et Duration)
		$errors = $validator->validate($service);

		// S'il y a une erreur et qu'au moins une contrainte n'est pas respectée
		// On retourne l'erreur sous forme de chaîne de caractère
		if (count($errors) > 0) {
			$errorsString = (string) $errors;
	
			return new Response($errorsString);
		}
		
		// puis on exécute la mise à jour en DB
		$em->flush();

		// On retourne le service mis à jour et le code 201 qui correspond au succès de l'opération
        return $this->json($service, 201);
    }

    /**
     * @Route("/api/v1/dashboard/company/services/{id}/delete", name="api_v1_company_services_delete", methods={"DELETE"}, requirements={"id"="\d+"})
     */
    public function delete(Service $service, EntityManagerInterface $em): Response
    {
		// Grâce au paramConverter j'ai récupéré le service que l'on souhaite supprimer
		$em->remove($service);

		// On exécute la suppression en base de données
		$em->flush();

        return $this->json("service deleted", 200);
    }
}
