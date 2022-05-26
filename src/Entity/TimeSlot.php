<?php

namespace App\Entity;

use App\Repository\TimeSlotRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=TimeSlotRepository::class)
 */
class TimeSlot
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\Column(type="date")
     * @Assert\NotBlank
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     */
    private $hours;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotBlank
     */
    private $is_booked;

    /**
     * @ORM\OneToOne(targetEntity=Appointment::class, mappedBy="time_slot", cascade={"persist", "remove"})
     * @Ignore()
     */
    private $appointment;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="timeSlots")
     * @ORM\JoinColumn(nullable=false)
     * @Ignore()
     */
    private $company;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getHours(): ?string
    {
        return $this->hours;
    }

    public function setHours(string $hours): self
    {
        $this->hours = $hours;

        return $this;
    }

    public function getIsBooked(): ?bool
    {
        return $this->is_booked;
    }

    public function setIsBooked(bool $is_booked): self
    {
        $this->is_booked = $is_booked;

        return $this;
    }

    public function getAppointment(): ?Appointment
    {
        return $this->appointment;
    }

    public function setAppointment(Appointment $appointment): self
    {
        // set the owning side of the relation if necessary
        if ($appointment->getTimeSlot() !== $this) {
            $appointment->setTimeSlot($this);
        }

        $this->appointment = $appointment;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }
}
