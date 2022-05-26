<?php

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Customer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Customer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Customer[]    findAll()
 * @method Customer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    public function getNextCustomerAppointements($id)
    {
        {
            $conn = $this->getEntityManager()->getConnection();

            $sql = '
            SELECT service.name, company.company_name, company.address, company.postcode, company.city, time_slot.date, time_slot.hours, service.duration, service.price
            FROM appointment
            JOIN customer
            ON customer.id = appointment.customer_id
            JOIN service
            ON service.id = appointment.service_id
            JOIN time_slot
            ON time_slot.id = appointment.time_slot_id
            JOIN company
            ON company.id = time_slot.company_id
            WHERE time_slot.is_booked = 1
            AND customer.id = :id
            AND time_slot.date >= CURDATE()
                ';
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'id' => $id
            ]);

            // returns an array of arrays (i.e. a raw data set)
            return $stmt->fetchAllAssociative();
        }
    }

    public function getPastCustomerAppointements($id)
    {
        {
            $conn = $this->getEntityManager()->getConnection();

            $sql = '
            SELECT service.name, company.company_name, company.address, company.postcode, company.city, time_slot.date, time_slot.hours, service.duration, service.price
            FROM appointment
            JOIN customer
            ON customer.id = appointment.customer_id
            JOIN service
            ON service.id = appointment.service_id
            JOIN time_slot
            ON time_slot.id = appointment.time_slot_id
            JOIN company
            ON company.id = time_slot.company_id
            WHERE time_slot.is_booked = 1
            AND customer.id = :id
            AND time_slot.date < CURDATE()
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
    //  * @return Customer[] Returns an array of Customer objects
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
    public function findOneBySomeField($value): ?Customer
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
