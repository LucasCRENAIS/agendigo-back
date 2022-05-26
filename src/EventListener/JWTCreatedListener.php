<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class JWTCreatedListener
{
	/**
	* @param JWTCreatedEvent $event
	*
	* @return void
	*/
	public function onJWTCreated(JWTCreatedEvent $event)
	{
		$payload = $event->getData();
		$user = $event->getUser();
		$roles = $user->getRoles();
		
		if ($roles[0] === "ROLE_CUST")
		{
			$customer = $user->getCustomer();

			$customer_id = $customer->getId();
			$customer_firstname = $customer->getFirstName();
			$customer_lastname = $customer->getLastName();

			$payload['data_jwt'] = array(
				'customer_id' => $customer_id,
				'customer_firstname' => $customer_firstname,
				'customer_lastname' => $customer_lastname,
			);
		}

		if ($roles[0] === "ROLE_PRO")
		{
			$company = $user->getCompany();

			$company_id = $company->getId();
			$company_name = $company->getCompanyName();
			$company_firstname = $company->getFirstName();
			$company_lastname = $company->getLastName();

			$payload['data_jwt'] = array(
				'company_id' => $company_id,
				'company_name' => $company_name,
				'company_firstname' => $company_firstname,
				'company_lastname' => $company_lastname,
			);
		}
		
		if (!$user instanceof UserInterface) {
			return;
		}

		$event->setData($payload);
	}
}