<?php
import('lib.pkp.classes.plugins.GenericPlugin');
class RemotesPlugin extends GenericPlugin {
        public function register($category, $path, $mainContextId = NULL) {
                $success = parent::register($category, $path);
                if ($success && $this->getEnabled()) {
                        // Ask for external or remote landing pages and galleys
                        HookRegistry::register('remotesform::display', array($this, 'getRemotes'));
                        // Display the publication statement on the article details page
                        HookRegistry::register('Templates::Article::Main', [$this, 'addPublicationStatement']);
                }
                return $success;
        }

        /**
         * Provide a name for this plugin
         *
         * The name will appear in the Plugin Gallery where editors can
         * install, enable and disable plugins.
         *
         * @return string
         */
        public function getDisplayName() {
                return __('plugins.generic.remotes.displayName');
        }

        /**
         * Provide a description for this plugin
         *
         * The description will appear in the Plugin Gallery where editors can
         * install, enable and disable plugins.
         *
         * @return string
         */
        public function getDescription() {
                return __('plugins.generic.remotes.description');
        }

        /**
         * Enable the settings form in the site-wide plugins list
         *
         * @return string
         */
        public function isSitePlugin() {
                return true;
        }


        /**
         * Add a settings action to the plugin's entry in the
         * plugins list.
         *
         * @param Request $request
         * @param array $actionArgs
         * @return array
         */
        public function getActions($request, $actionArgs) {

                // Get the existing actions
                $actions = parent::getActions($request, $actionArgs);

                // Only add the settings action when the plugin is enabled
                if (!$this->getEnabled()) {
                        return $actions;
                }

                // Create a LinkAction that will make a request to the
                // plugin's `manage` method with the `settings` verb.
                $router = $request->getRouter();
                import('lib.pkp.classes.linkAction.request.AjaxModal');
                $linkAction = new LinkAction(
                        'settings',
                        new AjaxModal(
                                $router->url(
                                        $request,
                                        null,
                                        null,
                                        'manage',
                                        null,
                                        [
                                                'verb' => 'settings',
                                                'plugin' => $this->getName(),
                                                'category' => 'generic'
                                        ]
                                ),
                                $this->getDisplayName()
                        ),
                        __('manager.plugins.settings'),
                        null
                );

                // Add the LinkAction to the existing actions.
                // Make it the first action to be consistent with
                // other plugins.
                array_unshift($actions, $linkAction);

                return $actions;
        }

        /**
         * Show and save the settings form when the settings action
         * is clicked.
         *
         * @param array $args
         * @param Request $request
         * @return JSONMessage
         */
        public function manage($args, $request) {
                switch ($request->getUserVar('verb')) {
                        case 'settings':

                                // Load the custom form
                                $this->import('RemotesPluginSettingsForm');
                                $form = new RemotesPluginSettingsForm($this);

                                // Fetch the form the first time it loads, before
                                // the user has tried to save it
                                if (!$request->getUserVar('save')) {
                                        $form->initData();
                                        return new JSONMessage(true, $form->fetch($request));
                                }

                                // Validate and save the form data
                                $form->readInputData();
                                if ($form->validate()) {
                                        $form->execute();
                                        return new JSONMessage(true);
                                }
                }
                return parent::manage($args, $request);
        }

        /**
         * Add the publication statement to the article details page.
         *
         * @param string $hookName string
         * @param array $params [[
         *      @option array Additional parameters passed with the hook
         *      @option TemplateManager
         *      @option string The HTML output
         * ]]
         * @return boolean
         */
        function addPublicationStatement($hookName, $params) {

                // Get the publication statement for this journal or press
    $context = Application::get()->getRequest()->getContext();
    $contextId = $context ? $context->getId() : CONTEXT_SITE;
                $publicationStatement = $this->getSetting($contextId, 'publicationStatement');

                // If the journal or press does not have a publication statement,
                // check if there is one saved for the site.
                if (!$publicationStatement && $contextId !== CONTEXT_SITE) {
                        $publicationStatement = $this->getSetting(CONTEXT_SITE, 'publicationStatement');
                }

                // Do not modify the output if there is no publication statement
                if (!$publicationStatement) {
                        return false;
                }

                // Add the publication statement to the output
                $output =& $params[2];
                $output .= '<p class="publication-statement">' . PKPString::stripUnsafeHtml($publicationStatement) . '</p>';

                return false;
        }

        function getRemotes($hookName, $params) {
                $templateMgr = TemplateManager::getManager();
                $submission =& $templateMgr->getTemplateVars('submission');

                $notificationLabel = '<span class="label">'.$this->getDisplayName().'</span>';
        }

}



