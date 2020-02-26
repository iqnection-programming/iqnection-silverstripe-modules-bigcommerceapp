<?php

namespace IQnection\BigCommerceApp\App;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use IQnection\BigCommerceApp\Entities\WebhookEntity;
use SilverStripe\Forms;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use IQnection\BigCommerceApp\Control\Listener;

class Webhooks extends Main
{
	private static $hidden = false;
	private static $managed_class = WebhookEntity::class;
	private static $url_segment = '_bc/webhooks';
	
	private static $allowed_actions = [
		'webhookSubscribeForm',
		'deleteWebhook'
	];
	
	private static $nav_links = [
		'Webhooks' => [
			'path' => '',
			'icon' => 'lock',
			'target' => '_blank'
		]
	];
	
	public function index()
	{
		$currentWebhooks = WebhookEntity::getAll();
		return $this->Customise([
			'BCWebhooks' => $currentWebhooks
		]);
	}
	
	public function webhookSubscribeForm()
	{
		$fields = Forms\FieldList::create();
		$entity = WebhookEntity::create([]);
		$fields->push( Forms\GroupedDropdownField::create('scope','Scope')
			->setSource($entity->Config()->get('scopes'))
			->addExtraClass('required')
			->setEmptyString('-- Select --') );
		
		$listener = Listener::create();
		$fields->push( Forms\TextField::create('destination','Listener URL')
			->setAttribute('placeholder', '/'.$listener->Link())
			->setValue('/'.$listener->Link()) );
			
		$actions = Forms\FieldList::create(
			Forms\FormAction::create('doSaveWebhook','Save')
		);
		
		$validator = Forms\RequiredFields::create(['scope','destination']);
		
		$form = Forms\Form::create(
			$this,
			'webhookSubscribeForm',
			$fields,
			$actions,
			$validator
		);
		$this->BootstrapForm($form);
		return $form;
	}
	
	public function doSaveWebhook($data, $form)
	{
		$scopeCategories = WebhookEntity::Config()->get('scopes');
		$validScope = false;
		foreach($scopeCategories as $scopes)
		{
			if (array_key_exists($data['scope'],$scopes))
			{
				$validScope = $data['scope'];
				break;
			}
		}
		if (!$validScope)
		{
			$this->addAlert('You must choose a scope','danger');
			return $this->redirectBack();
		}
		if (!$data['destination'])
		{
			$this->addAlert('A webhook needs a destination','danger');
			return $this->redirectBack();
		}
		$destination = Director::absoluteURL($data['destination']);
		$destination = preg_replace('/^http\:/','https:',$destination);
		$entity = WebhookEntity::create([
			'scope' => $validScope,
			'destination' => $destination,
			'is_active' => true,
			'headers' => $this->getAdditionalHeaders()
		]);
		
		try {
			$entity->Sync();
			WebhookEntity::getAll(true);
			$this->addAlert('Webhook Saved!');
		} catch (\Exception $e) {
			$this->addAlert($e->getMessage(), 'danger');
			if (method_exists($e,'getResponseBody'))
			{
				$this->addAlert($e->getResponseBody(), 'danger');
			}
		}
		return $this->redirectBack();
	}
	
	public function deleteWebhook()
	{
		$id = $this->getRequest()->param('ID');
		$entity = WebhookEntity::create(['id' => $id]);
		try {
			$entity->delete();
			WebhookEntity::getAll(true);
			$this->addAlert('Webhook Removed');
		} catch (\Exception $e) {
			$this->addAlert($e->getMessage(),'danger');
			if (method_exists($e,'getResponseBody'))
			{
				$this->addAlert($e->getResponseBody(), 'danger');
			}
		}
		return $this->redirectBack();
	}
	
	public function getAdditionalHeaders()
	{
		$headers = $this->Config()->get('additional_headers');
		$this->invokeWithExtensions('updateAdditionalHeaders',$headers);
		return $headers;
	}
}



