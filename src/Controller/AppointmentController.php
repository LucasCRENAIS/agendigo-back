<?php

namespace App\Controller;

use DateTime;
use App\Entity\TimeSlot;
use App\Entity\Appointment;
use App\Entity\User;
use App\Service\Mailer;
use App\Repository\ServiceRepository;
use App\Repository\CompanyRepository;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AppointmentController extends AbstractController
{
    /**
     * @Route("/api/v1/appointments/{id}/delete", name="api_v1_appointments_delete", methods={"DELETE"}, requirements={"id"="\d+"})
     */
    public function delete(Appointment $appointment, EntityManagerInterface $em, Mailer $mailer): Response
    {

        // on récupère l'id du timeslot correspondant à l'id de l'appoitments récupéré par le param converter
        $timeslot = $appointment->getTimeSlot();
        // on supprime le l'id du timeslot
        // on est obligé de le supprimé avant de supprimer l'appoitment à cause des contraintes de la BDD
        $em->remove($timeslot);
        // et on supprime l'appointement en lien
        $em->remove($appointment);

        $em->flush();
        // on envoi les mails de confirmation à l'utilisateur et à l'entreprise
        $mailer->sendDeletedAppointmentEmail($appointment->getCustomer()->getEmail());
        $mailer->sendDeletedAppointmentEmail($appointment->getTimeSlot()->getCompany()->getEmail());

        return $this->json("Appointment deleted", 200);
    }


    /**
     * @Route("/api/v1/appointments/company/{id}/create", name="api_v1_appointments_create", methods={"POST"}, requirements={"id"="\d+"})
     */
    public function create($id, Mailer $mailer, Request $request, EntityManagerInterface $em, CustomerRepository $customerRepository, ServiceRepository $serviceRepository, CompanyRepository $companyRepository, ValidatorInterface $validator): Response

    {
        // avant d'ajouter un appointment, il est nécéssaire d'ajouter un timeslot à cause des contraintes de la bdd

        // on récupère et on décode le contenu fourni en front grace à l'objet request
        $data = json_decode($request->getContent(), true);

        if (($this->getUser()) == null)
        {
            return $this->json("Vous devez être connecté pour réserver un rendez-vous", 400);
        }

        $timeslot = new TimeSlot();
        // si il y a bien un id récupéré en paramètre
        if (isset($id)) {
            $company = $companyRepository->find($id);
            // on défini le company_id de la table timeslot          
            $timeslot->setCompany($company);
        }
        if (isset($data["date"])) {
            // si la date est définie
            $date = $data["date"];
            // on la récupère et on la transforme en objet datetime
            $date = date_create_from_format('d-m-Y', $date);
            // on défini cette valeur en bdd
            $timeslot->setDate($date);
        }
        if (isset($data["hours"])) {
            $hours = $data["hours"];
            $timeslot->setHours($hours);
        }
        // on précise que le rendez-vous est résérvé
        $timeslot->setIsBooked(true);
        // on ajoute une date de création, demandée par la bdd
        $timeslot->setCreatedAt(new DateTime());

        // On vérifie les contraintes de validation
		$errors = $validator->validate($timeslot);

		// S'il y a une erreur et qu'au moins une contrainte n'est pas respectée
		// On retourne l'erreur sous forme de chaîne de caractère
		if (count($errors) > 0) {
			$errorsString = (string) $errors;

			return new Response($errorsString);
		}

        // on exécute les modfications en bdd
        $em->persist($timeslot);
        $em->flush();

        // Il faut instancier la classe Appointment afin de configurer le nouveau rdv qui va être ajouté en base de donnée
        $appointment = new Appointment();

        // On vérifie si des informations sont renseignées pour chaque ligne du tableau
        // Si oui, on ajoute l'information en base de données
        if (($this->getUser()->getCustomer()) != null) {
            $customer = $this->getUser()->getCustomer();
            $appointment->setCustomer($customer);
        }

        if (isset($data["service_id"])) {
            $service = $serviceRepository->find($data["service_id"]);
            $appointment->setService($service);
        }

        $appointment->setTimeSlot($timeslot);

        $appointment->setCreatedAt(new \DateTime());

        // On vérifie les contraintes de validation
		$errors = $validator->validate($appointment);

		// S'il y a une erreur et qu'au moins une contrainte n'est pas respectée
		// On retourne l'erreur sous forme de chaîne de caractère
		if (count($errors) > 0) {
			$errorsString = (string) $errors;

			return new Response($errorsString);
		}

        // on execute les changements en bdd
        $em->persist($appointment);
        $em->flush();

        // on envoi les mails de confirmation à l'utilisateur
        // d'abord, on range les informations dans des variables
        $appointmentDate = $appointment->getTimeSlot()->getDate();
        $appointmentHour = $appointment->getTimeSlot()->getHours();
        $appointmentDetails = [
            'Prestation' => $appointment->getService()->getName(),
            'avec' => $appointment->getTimeSlot()->getCompany()->getCompanyName(),
            'Adresse' => 
            $appointment->getTimeSlot()->getCompany()->getAddress() . ', '. 
            $appointment->getTimeSlot()->getCompany()->getPostcode().' '.
            $appointment->getTimeSlot()->getCompany()->getCity(),                         
            'Contact' => $appointment->getTimeSlot()->getCompany()->getEmail()
        ];
        // on passe ces variables en argument de notre service
        // appointmentDetails est un tableau pour eviter d'avoir 12 paramètres
        $mailer->sendAppointmentEmail($appointment->getCustomer()->getEmail(), $appointmentDetails, $appointmentDate, $appointmentHour);

        // et à l'entreprise, avec des détails spécifiques
        $customerDetails = [
            'Client' => $appointment->getCustomer()->getFirstname() . ' ' . $appointment->getCustomer()->getLastname(),
            'Prestation' => $appointment->getService()->getName(),
            'Téléphone' => $appointment->getCustomer()->getPhoneNumber(),
            'Mail' => $appointment->getCustomer()->getEmail()
        ];

        $mailer->sendAppointmentEmail($appointment->getTimeSlot()->getCompany()->getEmail(), $customerDetails, $appointmentDate, $appointmentHour);

        return $this->json($appointment, 201);
    }
}
