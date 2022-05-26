<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;



class CustomerController extends AbstractController
{
    /**
     * @Route("/api/v1/dashboard/customer/{id}", name="api_v1_customer_dashboard", methods = "GET", requirements={"id"="\d+"})
     */
    public function CustomerAppointments(CustomerRepository $customerRepository, $id, Customer $customer)
    {
        return $this->json([
            "firstname" => $customer->getFirstname(),
            "lastname"  => $customer->getLastname(),
            // requête custom qui ne retourne que les rendez-vous réservés
            "next_appointments" => $customerRepository->getNextCustomerAppointements($id),
            "past_appointments" => $customerRepository->getPastCustomerAppointements($id)
		], 200);
    }
    
    /**
     * @Route("/api/v1/dashboard/customer/{id}/profile/update", name="api_v1_customer_profile_update", methods = "PATCH", requirements={"id"="\d+"})
     */
    public function updateProfile(Request $request, Customer $customer, EntityManagerInterface $em, ValidatorInterface $validator)
    {
        // Decode les infos qui sont déjà en BDD
		$data = json_decode($request->getContent(), true);

        if(isset($data["firstname"])) 
        {
			$customer->setFirstname($data["firstname"]);
		}
        if(isset($data["lastname"])) 
        {
			$customer->setLastname($data["lastname"]);
		}
        if(isset($data["email"])) 
        {
			$customer->setEmail($data["email"]);
		}
        if(isset($data["phone_number"])) 
        {
            $customer->setPhoneNumber($data["phone_number"]);
        }
        if(isset($data["image"])) 
        {
			$customer->setImage($data["image"]);
		}
        if(isset($data["address"])) 
        {
			$customer->setAddress($data["address"]);
		}
        if(isset($data["city"])) 
        {
            $customer->setCity($data["city"]);
        }
        if(isset($data["postcode"])) 
        {
			$customer->setPostcode($data["postcode"]);
		}
		// On vérifie les contraintes de validation (Voir dans l'entité customer.php)
		$errors = $validator->validate($customer);

		// S'il y a une erreur et qu'au moins une contrainte n'est pas respectée
		// On retourne l'erreur sous forme de chaîne de caractère
		if (count($errors) > 0) {
			$errorsString = (string) $errors;

			return new Response($errorsString);
        }
        
		$em->flush();
        return $this->json($customer, 201);
    }

	/**
     *  @Route ("/api/v1/dashboard/customer/{id}/profile", name="api_v1_customer_profile_read", methods="GET", requirements={"id"="\d+"})
     */
    public function readProfile(Customer $customer)
    {
		return $this->json($customer, 200);
    }
}
