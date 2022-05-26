<?php

namespace App\Controller;

use App\Service\Mailer;
use App\Entity\Company;
use App\Entity\Customer;
use App\Entity\Day;
use App\Entity\User;
use App\Repository\ActivityRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
	/**
     * @Route("/api/v1/signin", name="api_v1_signin", methods={"POST"})
     */
    public function new(Request $request, UserPasswordEncoderInterface $passwordEncoder, EntityManagerInterface $em, ActivityRepository $activity, ValidatorInterface $validator, Mailer $mailer): Response
    {
		// Je récupère les données de la requête envoyée depuis le formulaire d'inscription
		$data = json_decode($request->getContent(), true);
		// TODO gérer le cas ou l'adresse mail existe déjà en bdd
		// Je créé un objet pour le nouvel utilisateur
        $user = new User();

		// J'associe l'adresse email (qui est aussi le username) à l'utilisateur
		$user->setEmail($data["email"]);

		// Je récupère le mot de passe en clair
		$rawPassword = $data["password"];

		if (! empty($rawPassword))
		{
			// J'encode le password
			$encodedPassword = $passwordEncoder->encodePassword($user, $rawPassword);
			// Je le renseigne dans l'objet User
			$user->setPassword($encodedPassword);
		}
		
		// Si la requête contient un company_name alors je créé un objet Company auquel j'associe les données
		if (isset($data["company_name"]))
		{
			$company = new Company();
			$user->setRoles(["ROLE_PRO"]);
			$company->setCreatedAt(new \DateTime);
			$company->setCompanyName($data["company_name"]);
			$company->setSirenNumber($data["siren_number"]);
			$company->setFirstname($data["firstname"]);
			$company->setLastname($data["lastname"]);
			$company->setEmail($data["email"]);
			$company->setActivity($activity->find($data["activity_id"]));

			$categoryOfActivity = $activity->find($data["activity_id"]);
			$category = $categoryOfActivity->getCategory();
			
			$company->setCategory($category);

			// J'utilise ce tableau pour pouvoir remplir les jours de la semaine
			$days = ["Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi", "Dimanche"];

			// Ici je boucle sur le tableau $days pour créer des horaires d'ouverture par défaut pour chaque nouvelle entreprise
			foreach($days as $day)
			{
				$weekDay = new Day();
				$weekDay->setCreatedAt(new \DateTime);
				$weekDay->setWeekDays($day);
				$weekDay->setAmOpening("08:00");
				$weekDay->setAmClosing(null);
				$weekDay->setPmOpening(null);
				$weekDay->setPmClosing("17:00");
				$weekDay->setCompany($company);
				$em->persist($weekDay);
			}

			// Validation des contraintes
			$errors = $validator->validate($company);
			if (count($validator->validate($company)) > 0) {
				$errorsString = (string) $errors;
	
				return new Response($errorsString);
			}

			$company->setUser($user);
			$em->persist($company);
			$user->setCompany($company);
		}
		
		// Si la requête NE CONTIENT PAS un company_name alors je créé un objet Customer auquel j'associe les données
		if (! isset($data["company_name"]))
		{
			$customer = new Customer();
			$user->setRoles(["ROLE_CUST"]);
			$customer->setCreatedAt(new \DateTime);
			$customer->setFirstname($data["firstname"]);
			$customer->setLastname($data["lastname"]);
			$customer->setEmail($data["email"]);

			$errors = $validator->validate($customer);
			if (count($validator->validate($customer)) > 0) {
				$errorsString = (string) $errors;
	
				return new Response($errorsString);
			}
			
			$customer->setUser($user);
			$em->persist($customer);
			$user->setCustomer($customer);
		}

		// On persiste les données du User avec la Company ou le Customer qui lui aura été associé précédemment
		$em->persist($user);

		// On effectue la création en DB
		$em->flush();
		// Ici, on envoi un mail de confirmation
		$mailer->sendSignInEmail($user->getEmail());
		return $this->json($user, 201);	
    }
}