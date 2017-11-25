<?php
namespace Craft;

Class BriefPlugin extends BasePlugin
{
	private $name = 'Brief';
	private $version = '2.0.1';
	private $schemaVersion = '0.1.0';
	private $description = 'The missing plugin for Craft user-group notifications.';
	private $developer = 'Taylor Daughtry';
	private $developerUrl = 'https://github.com/taylordaughtry';
	private $docUrl = 'https://github.com/taylordaughtry/craft-brief';
	private $feedUrl = 'https://raw.githubusercontent.com/taylordaughtry/Craft-Brief/master/brief/releases.json';

	public function getName()
	{
		return $this->name;
	}

	public function getVersion()
	{
		return $this->version;
	}

	public function getSchemaVersion()
	{
		return $this->schemaVersion;
	}

	public function getDescription()
	{
		return Craft::t($this->description);
	}

	public function getDeveloper()
	{
		return $this->developer;
	}

	public function getDeveloperUrl()
	{
		return $this->developerUrl;
	}

	public function getDocumentationUrl()
	{
		return $this->docUrl;
	}

	public function getReleaseFeedUrl()
	{
		return $this->feedUrl;
	}

	public function getSettingsHtml()
	{
		$settings = $this->getSettings();

		$settings['subject'] = base64_decode($settings['subject']);

		return craft()
			->templates
			->render('brief/settings', array(
				'settings' => $settings
			)
		);
	}

	public function onAfterInstall()
	{
		craft()->request->redirect(UrlHelper::getCpUrl('brief/welcome'));
	}

	public function init()
	{
		parent::init();

		craft()->on('entries.SaveEntry', function(Event $event) {
			$disabledEntries = $this->getSettings()['notifyDisabled'];

			if (! $disabledEntries && $event->params['entry']->status == 'disabled') {
				return;
			}

			$sectionId = $event->params['entry']->sectionId;

			foreach (craft()->userGroups->getAllGroups() as $group) {
				$groupId = $group->id;

				$permission = 'getnotifications:' . $sectionId;

				if (craft()->userPermissions->doesGroupHavePermission($groupId, $permission)) {

					craft()->brief->notifyUsers($event->params['entry'], $groupId);
				}
			}
		});
	}

	public function registerUserPermissions()
	{
		$sections = craft()->brief->getSections();

		$data = [];

		foreach ($sections as $key => $value) {
			$data['getnotifications:' . $key] = [
				'label' => 'Receives ' . $value . ' Notifications'
			];
		}

		return $data;
	}

	public function prepSettings($settings)
	{
		$settings['subject'] = base64_encode($settings['subject']);

		return $settings;
	}

	protected function defineSettings()
	{
		$defaultSubject = base64_encode('New entry for ' . craft()->getSiteName());

		return array(
			'customTemplate' => array(AttributeType::String, 'default' => ''),
			'notifyDisabled' => array(AttributeType::Bool, 'default' => false),
			'replyTo' => array(AttributeType::String, 'default' => ''),
			'slackWebhook' => array(AttributeType::String, 'default' => ''),
			'subject' => array(AttributeType::Mixed, 'default' => $defaultSubject)
		);
	}
}
