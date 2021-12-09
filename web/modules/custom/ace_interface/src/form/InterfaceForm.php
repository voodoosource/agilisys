<?php

namespace Drupal\ace_interface\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form definition for Interface integration.
 */
class InterfaceForm extends ConfigFormBase {

  /**
   * InterfaceForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ace_interface.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'interface_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->get('ace_interface.settings');

    \Drupal::messenger()
      ->addWarning('Showing the live settings, these may have been set in the local settings.interface.php');

    $form['environment'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Environment'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['environment']['trace'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Trace connection'),
      '#description' => $this->t('Trace Guzzle HTTP client'),
      '#default_value' => $config->get('trace'),
    ];
    $form['environment']['debug'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Debug Guzzle'),
      '#description' => $this->t('Guzzle HTTP client debug'),
      '#default_value' => $config->get('debug'),
    ];

    $form['submission'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Submission'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['submission']['submission_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Submission method'),
      '#default_value' => $config->get('submission_method'),
      '#options' => [
        'dynamics' => 'oData Dynamics',
        'submission_api' => 'Submission framework API',
      ],
    ];
    $form['submission']['ftp_root_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('FTP root path'),
      '#description' => $this->t('For Pantheon this should be in the format /srv/bindings/ect...'),
      '#default_value' => $config->get('ftp_root_path'),

    ];

    $form['secure_connection_info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Secure connection'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['secure_connection_info']['secure_connection'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use secure connection'),
      '#description' => $this->t('If true, the file submission framework will use the secure connection'),
      '#default_value' => $config->get('secure_connection'),
    ];
    $form['secure_connection_info']['disable_ssl_verify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable SSL verification for secure connection (emergencies only)'),
      '#description' => $this->t('Only use when Dynamics team hasnt configured the cert'),
      '#default_value' => $config->get('disable_ssl_verify'),
    ];
    $form['secure_connection_info']['file_secure_connection'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use secure connection for files'),
      '#description' => $this->t('If false, the file doc announcer will use the secure connection'),
      '#default_value' => $config->get('file_secure_connection'),
    ];
    $form['secure_connection_info']['soip_constant_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SOIP constant'),
      '#description' => $this->t('Constant used in curl connection.'),
      '#default_value' => $config->get('soip_constant_name'),
    ];


    $this->ace_interface_debug_render();

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ace_interface.settings')
      ->set('trace', $form_state->getValue('trace'))
      ->set('debug', $form_state->getValue('debug'))
      ->set('submission_method', $form_state->getValue('submission_method'))
      ->set('secure_connection', $form_state->getValue('secure_connection'))
      ->set('disable_ssl_verify', $form_state->getValue('disable_ssl_verify'))
      ->set('soip_constant_name', $form_state->getValue('soip_constant_name'))
      ->set('file_secure_connection', $form_state->getValue('file_secure_connection'))
      ->set('ftp_root_path', $form_state->getValue('ftp_root_path'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  public function ace_interface_debug_render($title = "Current Interface settings in database") {

    $config = \Drupal::configFactory()->getEditable('ace_interface.settings');
    $array_to_render = $config->getRawData();

    $markup = var_export($array_to_render, TRUE);


    $element = [
      '#markup' => $markup,
    ];

    $build = [
      '#type' => 'details',
      '#title' => $title,
    ];
    $build['xml'] = [
      '#type' => 'textarea',
      '#rows' => 10,
      '#cols' => 80,
      '#value' => $element,
    ];
    \Drupal::messenger()->addStatus(\Drupal::service('renderer')
      ->renderPlain($build));

    return;
  }


}
