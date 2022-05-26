<?php

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Activity|null find($id, $lockMode = null, $lockVersion = null)
 * @method Activity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Activity[]    findAll()
 * @method Activity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    // /**
    //  * @return Activity[] Returns an array of Activity objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */
   
    public function findAllByCity($city)
    {
        
        $em = $this->getEntityManager();
        $query = $em->createQuery("
            SELECT DISTINCT a.name, a.image
            FROM App\Entity\Company c
            INNER JOIN c.activity a
            WHERE c.city = :city
        ");
        $query->setParameter(':city', $city);
        return $query->getResult();
        ;
    }

    // public function findCompaniesByActivitiesByCities($city, $activity, $company)
    // {
        
    //     $em = $this->getEntityManager();
    //     $query = $em->createQuery("
    //         SELECT c
    //         FROM App\Entity\Company c
    //         WHERE c.city = :city 
    //         AND c.activity = :activity 
    //         AND c.company_name = :company
    //     ");
    //     $query->setParameter(':city', $city);
    //     $query->setParameter(':activity', $activity);
    //     $query->setParameter(':company', $company);
    //     return $query->getResult();
    //     ;
    // }
    
}
