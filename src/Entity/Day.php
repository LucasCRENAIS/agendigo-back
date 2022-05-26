<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\DayRepository;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=DayRepository::class)
 */
class Day
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $week_days;

    /**
     * @ORM\Column(type="string", length=255)
	 * @Assert\NotBlank
     */
    private $am_opening;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $am_closing;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pm_opening;

    /**
     * @ORM\Column(type="string", length=255)
	 * @Assert\NotBlank
     */
    private $pm_closing;

    /**
     * @ORM\ManyToOne(targetEntity=Company::class, inversedBy="days")
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

    public function getWeekDays(): ?string
    {
        return $this->week_days;
    }

    public function setWeekDays(string $week_days): self
    {
        $this->week_days = $week_days;

        return $this;
    }

    public function getAmOpening(): ?string
    {
        return $this->am_opening;
    }

    public function setAmOpening(string $am_opening): self
    {
        $this->am_opening = $am_opening;

        return $this;
    }

    public function getAmClosing(): ?string
    {
        return $this->am_closing;
    }

    public function setAmClosing(?string $am_closing): self
    {
        $this->am_closing = $am_closing;

        return $this;
    }

    public function getPmOpening(): ?string
    {
        return $this->pm_opening;
    }

    public function setPmOpening(?string $pm_opening): self
    {
        $this->pm_opening = $pm_opening;

        return $this;
    }

    public function getPmClosing(): ?string
    {
        return $this->pm_closing;
    }

    public function setPmClosing(string $pm_closing): self
    {
        $this->pm_closing = $pm_closing;

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
