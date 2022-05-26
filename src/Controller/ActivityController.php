<?php

namespace App\Controller;
use App\Repository\ActivityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ActivityController extends AbstractController
{
    /**
     * @Route("/api/v1/cities/{city}/activities", name="api_v1_activities_list", methods={"GET"}, requirements={"city"="^[A-Za-zàáâãäåçèéêëìíîïðòóôõöùúûüýÿ]+$"})
     */
    public function findAllByCity(ActivityRepository $activityRepository, $city): Response
    {
        $activitiesByCity = $activityRepository->findAllByCity($city);
        return $this->json($activitiesByCity, 200);
    }
}
