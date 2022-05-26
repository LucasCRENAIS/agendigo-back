<?php

namespace App\Service;

use App\Entity\Company;
use App\Repository\DayRepository;
use App\Repository\ServiceRepository;
use App\Repository\TimeSlotRepository;

class AvailableTimeSlot

{
	protected $dayRepository;
	protected $timeSlotRepository;
	protected $serviceRepository;

	public function __construct(DayRepository $dayRepository, TimeSlotRepository $timeSlotRepository, ServiceRepository $serviceRepository)
	{
		$this->dayRepository = $dayRepository;
		$this->timeSlotRepository = $timeSlotRepository;
		$this->serviceRepository = $serviceRepository;
	}

	// Cette fonction permet d'enlever du planning tous les horaires auquel le RDV ne peut être pris
	private function emptyDailyPlanning($dailyPlanning, $bookedTimeSlots, $possibleTimeSlots, $date, $planning)
	{	
		foreach($bookedTimeSlots as $bookedTimeSlot)
		{
			// Cette variable permet de définir un interval de 10min
			$min10 = 600;

			// Je stocke le message à afficher pour un créneau pour lequel un RDV est déjà réservé ou en cours
			$notAvailable = "Non disponible";

			// Heure de début du RDV réservé
			$timeSlot = strtotime($bookedTimeSlot["hours"]);
			$bookedTimeSlotFormat = date('H:i', $timeSlot);
			
			// Durée en seconde du RDV réservé
			$bookedTimeSlotDurationInSeconds = $bookedTimeSlot["duration"] * 60;

			// Heure de fin du RDV réservé
			$endOfAppointment = $timeSlot + $bookedTimeSlotDurationInSeconds;
			$endFormat = date('H:i', $endOfAppointment);

			// Dans ce tableau je stocke tous les créneaux à interval de 10min entre le début et la fin du RDV réservé
			$timeSlotDuringAppt = [];
			for ($i = $min10; $i < $endOfAppointment - $timeSlot; $i += $min10)
			{
				$timeSlotNext10Min = date("H:i", $timeSlot + $i);
				array_push($timeSlotDuringAppt, $timeSlotNext10Min);
			}

			foreach($dailyPlanning as $index => $element)
			{
				// Je récupère l'heure de démarrage du prochain créneau
				$nextTimeSlotBeginning = strtotime($element) + ($possibleTimeSlots + 1) * $min10;
				
				// Dans un tableau je stocke tous les créneaux de 10min entre le début du créneau actuel et la fin du créneau actuel
				$possibleHoursInTimeSlot = [];
				for ($i = $min10; $i < $nextTimeSlotBeginning - strtotime($element); $i += $min10)
				{
					$timeSlotNext10Min = date("H:i", strtotime($element) + $i);
					array_push($possibleHoursInTimeSlot, $timeSlotNext10Min);
				}
				
				// Si l'heure du RDV réservé === un créneau du planning alors on retire ce créneau
				if($bookedTimeSlotFormat === $element)
				{
					$dailyPlanning[$index] = $notAvailable;
				}

				// Si l'heure de fin du RDV réservé fait partie des créneaux possiblement existant toutes les 10min
				// Entre le début et la fin du créneau réservé
				// Alors on supprime le créneau actuel de la prise de RDV car cela signifie que le RDV réservé avant le créneau actuel
				// se termine après le début du créneau actuel
				if(in_array($endFormat, $possibleHoursInTimeSlot))
				{
					$dailyPlanning[$index] = $notAvailable;
				}

				// Si le créneau sur lequel on est en train de boucler est présent dans le tableau $timeSlotDuringAppt
				// cela signifie qu'un RDV est déjà en cours au même instant
				// donc on supprime le créneau
				if(in_array($element, $timeSlotDuringAppt))
				{
					$dailyPlanning[$index] = $notAvailable;
				}

				// Si le créneau ne correspond pas à l'heure d'un RDV réservé ou qu'un RDV réservé ne se termine pas après le début du créneau actuel
				// Je vérifie si un RDV n'est pas déjà existant entre le début du créneau actuel et la fin du créneau actuel
				// Si oui alors on enlève le créneau des horaires dispo à la prise de RDV
				if(isset($dailyPlanning[$index]))
				{
					foreach($possibleHoursInTimeSlot as $timeSlotElement)
					{
						if($timeSlotElement === $bookedTimeSlotFormat)
						{
							$dailyPlanning[$index] = $notAvailable;
						}
					}
				}	
			}
		}

		// Ici je stocke le message à afficher lorsque l'entreprise est fermée
		$closed = "Fermé";

		// Je vérifie la date du jour et l'heure actuelle
		// Si l'heure actuelle est supérieure à un créneau alors on enlève le créneau du tableau
		$currentDate = date('Y-m-d');
		$now = time();
		foreach($dailyPlanning as $index => $element)
		{
			$elementTime = strtotime($element);
			if ($date == $currentDate && $elementTime < $now)
			{
				$dailyPlanning[$index] = $closed;
			}
		}
		
		if (! empty($dailyPlanning))
		{
			array_push($planning, $dailyPlanning);
		} else {
			$dailyPlanning[0] = $closed;
			array_push($planning, $dailyPlanning);
		}
		return $planning;
	}

	public function listAvailableTimeSlots(Company $company, Int $serviceId)
    {
		// Je récupère les horaires d'ouverture que la société a définit dans son profile du Lundi au Dimanche
		$openingDays = $this->dayRepository->getDaysByCompany($company->getId());
		
		// Je récupère le service que le client souhaite réserver
		$service = $this->serviceRepository->find($serviceId);
		
		// Durée du service que le client souhaite réserver
		$duration = $service->getDuration();
	
		$possibleTimeSlots = $duration / 10 - 1;

		// Dans ce tableau je stocke tout le planning de la semaine d'une entreprise
		$weeklySchedule = [];

		// Ici je rempli chaque jour avec tous les créneaux possibles en fonction des horaires d'ouverture
		foreach($openingDays as $day)
		{
			if($day["am_closing"] === null && $day["pm_opening"] === null)
			{
				// Dans ce tableau je stocke les créneaux du jour sur lequel on est en train de boucler
				$dailySchedule = [];

				// Conversion de l'heure d'ouverture de string à timestamp
				$amOpening = strtotime($day["am_opening"]);

				// Stocke heure de fermeture de chaque jour
				$pmClosing = strtotime($day["pm_closing"]);

				$timeSlotDuration = $duration * 60;
				// On remplit le tableau avec tous les créneaux de la journée
				for ($i = 0; $i < $pmClosing - $amOpening; $i += $timeSlotDuration) 
				{
					$amOpeningFormat = date('H:i', $amOpening + $i);

					array_push($dailySchedule, $amOpeningFormat);
				}
                
				// Je récupère la dernière heure possible pour la prise de RDV
				$lastElementInArray = end($dailySchedule);

				// Si la durée du service ajoutée à la dernière est supérieure à l'heure de fermeture
				// alors on retire le dernier créneau du tableau pour respecter les horaires de l'entreprise
				if (strtotime($lastElementInArray) + $duration * 60 > strtotime($day["pm_closing"])) {
					$lastIndex = array_key_last($dailySchedule);
					unset($dailySchedule[$lastIndex]);
				}
                
				$indexedDailySchedule = [];
				for ($i = 0; $i < count($dailySchedule); $i++)
				{
					$indexedDailySchedule[$i] = $dailySchedule[$i];
				}

				// On ajoute le tableau dans le tableau des créneaux de la semaine
				array_push($weeklySchedule, $indexedDailySchedule);
			}

			if($day["am_closing"] !== null && $day["pm_opening"] !== null)
			{
				$dailySchedule = [];
				$morningSchedule = [];
				$afternoonSchedule = [];

				$amOpening = strtotime($day["am_opening"]);
				$amClosing = strtotime($day["am_closing"]);

				$timeSlotDuration = $duration * 60;

				// Je remplis le tableau avec les créneaux du matin
				for($i = 0; $i < $amClosing - $amOpening; $i += $timeSlotDuration)
				{
					$amOpeningFormat = date('H:i', $amOpening + $i);
					array_push($morningSchedule, $amOpeningFormat);
				}

				// Je récupère la dernière heure possible pour la prise de RDV
				$lastElementInArray = end($morningSchedule);

				// Si la durée du service ajoutée à la dernière est supérieure à l'heure de fermeture du matin
				// alors on retire le dernier créneau du tableau pour respecter les horaires de l'entreprise
				if (strtotime($lastElementInArray) + $duration * 60 > strtotime($day["am_closing"])) {
					$lastIndex = array_key_last($morningSchedule);
					unset($morningSchedule[$lastIndex]);
				}
                

				$pmOpening = strtotime($day["pm_opening"]);
				$pmClosing = strtotime($day["pm_closing"]);

				$timeSlotDuration = $duration * 60;

				// Je remplis le tableau avec les créneaux du matin
				for($i = 0; $i < $pmClosing - $pmOpening; $i += $timeSlotDuration)
				{
					$pmOpeningFormat = date('H:i', $pmOpening + $i);
					array_push($afternoonSchedule, $pmOpeningFormat);
				}

				// Je récupère la dernière heure possible pour la prise de RDV
				$lastElementInArray = end($afternoonSchedule);

				// Si la durée du service ajoutée à la dernière est supérieure à l'heure de fermeture de l'après-midi
				// alors on retire le dernier créneau du tableau pour respecter les horaires de l'entreprise
				if(strtotime($lastElementInArray) + $duration * 60 > strtotime($day["pm_closing"]))
				{
					$lastIndex = array_key_last($afternoonSchedule);
					unset($afternoonSchedule[$lastIndex]);
				}	

				// On merge les 2 tableaux en 1 et on les ajoute au tableau des créneaux pour toute la semaine
				$dailySchedule = array_merge($morningSchedule, $afternoonSchedule);

				$indexedDailySchedule = [];
				for ($i = 0; $i < count($dailySchedule); $i++)
				{
					$indexedDailySchedule[$i] = $dailySchedule[$i];
				}
				array_push($weeklySchedule, $indexedDailySchedule);
			}
		}

		// Je récupère tous les jours sur les 3 prochains mois
		$currentDay = new \Datetime('NOW');

		// Tableau dans lequel on stocke tous les jours sur les 3 prochains mois
		$datesArr = [$currentDay->format('Y-m-d')];
		for ($i=1; $i<91 ; $i++) 
		{
			$currentDay->modify('+1 day');
			array_push($datesArr, $currentDay->format('Y-m-d'));
		}
		
		$planning = [];
		foreach($datesArr as $date)
		{
			// Récupérer tous les timeslots de la compagnie qui sont booked en fonction de la date
			$bookedTimeSlots = $this->timeSlotRepository->getBookedTimeSlots($company->getId(), $date);
			
			// Si le jour est un Lundi
			// On vérifie s'il y a des RDV réservés et on les retire des horaires ouverts à la prise de RDV
			// On ajoute le tableau au planning global des 3 prochains mois
			if (date('D', strtotime($date)) == "Mon") 
			{
				$dailyPlanning = $weeklySchedule[0];

				$planning = $this->emptyDailyPlanning($dailyPlanning, $bookedTimeSlots, $possibleTimeSlots, $date, $planning);
			}

			// Si le jour est un Mardi
			if (date('D', strtotime($date)) == "Tue") 
			{
				$dailyPlanning = $weeklySchedule[1];

				$planning = $this->emptyDailyPlanning($dailyPlanning, $bookedTimeSlots, $possibleTimeSlots, $date, $planning);
			}

			// Si le jour est un Mercredi
			if (date('D', strtotime($date)) == "Wed") 
			{
				$dailyPlanning = $weeklySchedule[2];

				$planning = $this->emptyDailyPlanning($dailyPlanning, $bookedTimeSlots, $possibleTimeSlots, $date, $planning);
			}

			// Si le jour est un Jeudi
			if (date('D', strtotime($date)) == "Thu") 
			{
				$dailyPlanning = $weeklySchedule[3];

				$planning = $this->emptyDailyPlanning($dailyPlanning, $bookedTimeSlots, $possibleTimeSlots, $date, $planning);
			}
			
			// Si le jour est un Vendredi
			if (date('D', strtotime($date)) == "Fri") 
			{
				$dailyPlanning = $weeklySchedule[4];

				$planning = $this->emptyDailyPlanning($dailyPlanning, $bookedTimeSlots, $possibleTimeSlots, $date, $planning);
			}

			// Si le jour est un Samedi
			if (date('D', strtotime($date)) == "Sat") 
			{
				$dailyPlanning = $weeklySchedule[5];

				$planning = $this->emptyDailyPlanning($dailyPlanning, $bookedTimeSlots, $possibleTimeSlots, $date, $planning);
			}

			// Si le jour est un Dimanche
			if (date('D', strtotime($date)) == "Sun") 
			{
				$dailyPlanning = $weeklySchedule[6];

				$planning = $this->emptyDailyPlanning($dailyPlanning, $bookedTimeSlots, $possibleTimeSlots, $date, $planning);
			}
		}

		// Ici je m'occupe d'ajouter la date du jour en index du tableau final et je formate la date au format Fr
		$currentDayFormat = new \Datetime('NOW');

		// Tableau dans lequel on stocke tous les jours sur les 3 prochains mois
		$datesArrFormat = [$currentDayFormat->format('d-m-Y')];
		for ($i=1; $i<91 ; $i++) 
		{
			$currentDayFormat->modify('+1 day');
			array_push($datesArrFormat, $currentDayFormat->format('d-m-Y'));
		}

		// Dans un tableau, on stocke la date du jour à la clé "date"
		// et les heures du jour dispo à la prise de RDV à la clé "hours"
		$combinedPlanning = [];
		for ($i=0; $i<91 ; $i++) 
		{
			$currentDate = $datesArrFormat[$i];
			$currentPlanning = $planning[$i];

			$combinedPlanning[$i]["date"] = $currentDate;
			$combinedPlanning[$i]["hours"] = $currentPlanning;
		}

		// On retourne le planning complet
		return $combinedPlanning;
    }
}