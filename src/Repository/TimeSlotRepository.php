<?php

namespace App\Repository;

use App\Entity\TimeSlot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TimeSlot|null find($id, $lockMode = null, $lockVersion = null)
 * @method TimeSlot|null findOneBy(array $criteria, array $orderBy = null)
 * @method TimeSlot[]    findAll()
 * @method TimeSlot[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TimeSlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeSlot::class);
    }

	public function getBookedTimeSlots(int $id, $date)
	{
		$conn = $this->getEntityManager()->getConnection();

        $sql = '
				SELECT date, hours, is_booked, appointment.service_id, service.duration
				FROM time_slot
				INNER JOIN appointment
				INNER JOIN service
				WHERE time_slot.id = appointment.time_slot_id
				AND appointment.service_id = service.id
				AND is_booked = 1
				AND date= :date
				AND time_slot.company_id = :id
            ';
        $stmt = $conn->prepare($sql);
        $stmt->execute([
			'id' => $id,
			'date' => $date
			]);

        // returns an array of arrays (i.e. a raw data set)
        return $stmt->fetchAllAssociative();
	}

    // /**
    //  * @return TimeSlot[] Returns an array of TimeSlot objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TimeSlot
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
