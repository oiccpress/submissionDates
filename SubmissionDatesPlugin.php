<?php

/**
 * Main class for submission dates plugin
 * 
 * @author Joe Simpson
 * 
 * @class SubmissionDatesPlugin
 *
 * @ingroup plugins_generic_submissionDates
 *
 * @brief Publisher Preferences
 */

namespace APP\plugins\generic\submissionDates;

use APP\core\Application;
use APP\template\TemplateManager;
use Illuminate\Support\Facades\DB;
use PKP\components\forms\FieldText;
use PKP\components\forms\FormComponent;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;

class SubmissionDatesPlugin extends GenericPlugin {

    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path);

        if ($success && $this->getEnabled()) {
            Hook::add('Form::config::before', [$this, 'addPublicationFormFields']);
            Hook::add('Publication::edit', [ $this, 'editPublication' ]);
            Hook::add('Schema::get::publication', [ $this, 'addToPublicationSchema' ]);
            Hook::add('Import::Publication::meta', [ $this, 'importDate' ]);
            // Hook::add('Import::Publication::revisedDate', [ $this, 'importRevisedDate' ]);
        }

        return $success;
    }

    /**
     * Provide a name for this plugin
     *
     * The name will appear in the Plugin Gallery where editors can
     * install, enable and disable plugins.
     */
    public function getDisplayName()
    {
        return 'Submission Dates';
    }

    /**
     * Provide a description for this plugin
     *
     * The description will appear in the Plugin Gallery where editors can
     * install, enable and disable plugins.
     */
    public function getDescription()
    {
        return 'This plugin provides available dates to display on an article/submission page to know when articles have been processed by the journal.';
    }

    public function editPublication(string $hookName, array $args): void
    {
        $publication = $args[0];
        $params = $args[2];
        $request = $this->getRequest();

        if(isset($params['submission_dates__revised'])) {
            $publication->setData( 'submission_dates__revised', $params['submission_dates__revised'] );
        }

        if(isset($params['submission_dates__accepted'])) {
            $publication->setData( 'submission_dates__accepted', $params['submission_dates__accepted'] );
        }

        if(isset($params['submission_dates__received'])) {
            $publication->setData( 'submission_dates__received', $params['submission_dates__received'] );
        }

    }

    public function addPublicationFormFields(string $hookName, FormComponent $form): void
    {
        if ($form->id !== 'metadata') {
            return;
        }

        $form->addField(new FieldDate('submission_dates__received', [
            'label' => __('plugins.submissionDates.received.displayName'),
            'value' => $form->publication->getData('submission_dates__received'),
        ]));

        $form->addField(new FieldDate('submission_dates__revised', [
            'label' => __('plugins.submissionDates.revised.displayName'),
            'value' => $form->publication->getData('submission_dates__revised'),
        ]));

        $form->addField(new FieldDate('submission_dates__accepted', [
            'label' => __('plugins.submissionDates.accepted.displayName'),
            'value' => $form->publication->getData('submission_dates__accepted'),
        ]));

    }

    public function addToPublicationSchema(string $hookName, array $args): bool
    {
        $schema = &$args[0];

        $schema->properties->{"submission_dates__revised"} = (object)[
            'type' => 'string',
            'multilingual' => false,
            'apiSummary' => true,
            'validation' => ['nullable']
        ];

        $schema->properties->{"submission_dates__accepted"} = (object)[
            'type' => 'string',
            'multilingual' => false,
            'apiSummary' => true,
            'validation' => ['nullable']
        ];

        $schema->properties->{"submission_dates__received"} = (object)[
            'type' => 'string',
            'multilingual' => false,
            'apiSummary' => true,
            'validation' => ['nullable']
        ];

        return false;

    }

    
    /**
     * This depends on a PR to PKP-lib being merged; otherwise this may change and/or not actualy work
     */
    public function importDate($hookName, array $args) {

        $tag = $args[0];
        $publication = $args[1];

        $meta_name = $tag->getAttribute("name");
        switch($meta_name) {
            case 'accepted_date':
                $publication->setData('submission_dates__accepted', $tag->textContent);
                return true;
            case 'revised_date':
                $publication->setData('submission_dates__revised', $tag->textContent);
                return true;
        }

    }

    /**
     * This depends on a PR to PKP-lib being merged; otherwise this may change and/or not actualy work
     */
    public function importRevisedDate($hookName, array $args) {

        $tag = $args[0];
        $publication = $args[1];

        $publication->setData('submission_dates__revised', $tag->textContent);

    }

}
