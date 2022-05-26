<?php

namespace App\Repository;
use App\Entity\TimeSlot;
use App\Entity\Company;
use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Company|null find($id, $lockMode = null, $lockVersion = null)
 * @method Company|null findOneBy(array $criteria, array $orderBy = null)
 * @method Company[]    findAll()
 * @method Company[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

	public function findCompaniesInActivity(string $city, string $activity): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
			SELECT company.*, activity.name
			FROM company
			INNER JOIN activity
			ON company.activity_id = activity.id
			WHERE company.city = :city AND activity.name = :activity
            ';
        $stmt = $conn->prepare($sql);
        $stmt->execute([
			'city' => $city,
			'activity' => $activity
			]);

        // returns an array of arrays (i.e. a raw data set)
        return $stmt->fetchAllAssociative();
    }


	public function findCities(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
			SELECT DISTINCT city
			FROM company
            WHERE city != "NULL" 
            AND city != ""
	    ORDER BY city ASC
            ';
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        // returns an array of arrays (i.e. a raw data set)
        return $stmt->fetchAllAssociative();
    }

    public function findBooked($id)
    {       
        $em = $this->getEntityManager();
        $query = $em->createQuery("
            SELECT t
            FROM App\Entity\TimeSlot t
            WHERE t.is_booked = 1
            AND t.company = :id

        ");
        $query->setParameter(':id', $id);
        return $query->getResult();
        ;
    }

    public function getAppointementDetails($id)
    {
        {
            $conn = $this->getEntityManager()->getConnection();

            $sql = '
            SELECT customer.firstname, customer.lastname, service.name, service.duration, time_slot.date, time_slot.hours
            FROM appointment
            JOIN customer
            ON customer.id = appointment.customer_id
            JOIN service
            ON service.id = appointment.service_id
            JOIN time_slot
            ON time_slot.id = appointment.time_slot_id
            WHERE time_slot.is_booked = 1 
            AND time_slot.company_id = :id
                ';
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'id' => $id
            ]);

            // returns an array of arrays (i.e. a raw data set)
            return $stmt->fetchAllAssociative();
        }

    }

    // /**
    //  * @return Company[] Returns an array of Company objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Company
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
