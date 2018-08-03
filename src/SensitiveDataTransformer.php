<?php

namespace Drupal\relaxed;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\encrypt\Entity\EncryptionProfile;

/**
 * Class SensitiveDataTransformer.
 *
 * @package Drupal\relaxed
 */
class SensitiveDataTransformer {

  /**
   * Encryption is enabled or not.
   *
   * @var bool
   */
  protected $encrypt;

  /**
   * The encryption profile to use.
   *
   * @var string
   */
  protected $encryptProfile;

  /**
   * Encryption service.
   *
   * @var Drupal\encrypt\EncryptionProfileManagerInterface
   */
  protected $encryption;

  /**
   * {@inheritdoc}
   */
  protected $encryptionProfile;

  /**
   * Constructs an Encrypt object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $settings = $config_factory->get('relaxed.settings');
    $this->encrypt = $settings->get('encrypt');
    $this->encryptProfile = $settings->get('encrypt_profile');
    if ($this->encrypt && $this->encryptProfile) {
      $this->setEncryption();
    }
  }

  /**
   * Sets up encryption if needed.
   */
  public function setEncryption() {
    // Load the encryption profile if available.
    $this->encryption = \Drupal::service('encryption');
    $this->encryptionProfile = EncryptionProfile::load($this->encryptProfile);
  }

  /**
   * Gets a value.
   *
   * @param string $value
   *   The value to transform.
   *
   * @return string
   *   The transformed value depending on the settings.
   */
  public function get($value) {
    $value = base64_decode($value);

    if ($this->encrypt) {
      $value = $this->encryption->decrypt($value, $this->encryptionProfile);
    }

    return $value;
  }

  /**
   * Sets a value.
   *
   * @param string $value
   *   The value to transform.
   *
   * @return string
   *   The transformed value depending on the settings.
   */
  public function set($value) {
    if ($this->encrypt) {
      $value = $this->encryption->encrypt($value, $this->encryptionProfile);
    }

    return base64_encode($value);
  }

}
