<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthenticationSuccessListener
{
	/**
	 * @param AuthenticationSuccessEvent $event
	 */
	public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
	{
		$data = $event->getData();
		$user = $event->getUser();
		$roles = $user->getRoles();
		
		if ($roles[0] === "ROLE_CUST")
		{
			$customer = $user->getCustomer();

			$customer_id = $customer->getId();
			$customer_firstname = $customer->getFirstName();
			$customer_lastname = $customer->getLastName();

			$data['data_public'] = array(
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

			$data['data_public'] = array(
				'company_id' => $company_id,
				'company_name' => $company_name,
				'company_firstname' => $company_firstname,
				'company_lastname' => $company_lastname,
			);
		}
		
		if (!$user instanceof UserInterface) {
			return;
		}

		$event->setData($data);
	}
}
