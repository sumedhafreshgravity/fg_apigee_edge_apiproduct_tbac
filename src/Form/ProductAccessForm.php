<?php

namespace Drupal\fg_apigee_edge_apiproduct_tbac\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\apigee_edge\Entity\ApiProductInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Database\Database;
use \Drupal\Core\Url;
use  \Symfony\Component\HttpFoundation\RedirectResponse;

class ProductAccessForm extends FormBase {
/**
 * returns formID
 *
 * @return void
 */
  public function getFormId() {
    // Here we set a unique form id
    return 'product_access_mapping_form';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Here we build the form UI.// Textfield form element.
    // URL query parameter.
    $org_name =  \Drupal::request()->query->get('teamrole');

    $api_product_storage = \Drupal::entityTypeManager()->getStorage('api_product');
     /** @var \Drupal\apigee_edge\Entity\ApiProductInterface[] $api_products */
    $api_products = $api_product_storage->loadMultiple();
    // Sort products alphabetically (display name is an attribute so sorting in
    // the query level does not work).
    uasort($api_products, function (ApiProductInterface $a, ApiProductInterface $b) {
      // Ignore case and malicious characters.
      return strcmp(mb_strtolower(Xss::filter($a->getDisplayName())), mb_strtolower(Xss::filter($b->getDisplayName())));
    });
    foreach ($api_products as $product_name => $product) {
      $product_names[$product_name] = $product->getDisplayName();
    }

    // Check for existing product Mapping
    $query = \Drupal::database()->select('org_product_mapping', 't');
    $query->addField('t', 'allowed_product');
    $query->condition('t.org_id', $org_name);
    $query->distinct();
    $result = $query->execute()->fetchAll();
    $allwed_products = array();
    foreach($result as $ap) {
      $allwed_products[]=$ap->allowed_product;
    }

    $form['org_name'] = [
      '#type' => 'textfield',
      '#title' => 'Organization Name:',
      '#value' => $org_name,
      '#required' => TRUE,
      '#attributes' => array('readonly' => 'readonly'),
    ];

    // Radio buttons form element.
    $form['api_product_list'] = [
      '#type' => 'checkboxes',
      '#title' => 'API Products:',
      '#options' => $product_names,
      '#default_value' => $allwed_products,
    ];

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Assign API Product',
    );

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Here we decide whether the form can be forwarded to the submitForm()
    // function or should be sent back to the user to fix some information.
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $api_produc_list = $form_state->getValues()['api_product_list'];
    $org_name = $form_state->getValues()['org_name'];
    $conn = Database::getConnection();

    // Delete earler mapping and add new one in table.
    $query = \Drupal::database()->delete('org_product_mapping');
    $query->condition('org_id', $org_name);
    $query->execute();

    // add new mapping  in table.
    foreach ( $api_produc_list as $product_id => $product_name) {
      if($product_name  !== 0) {
        $conn->insert('org_product_mapping')->fields(
          array(
            'org_id' => $org_name,
            'allowed_product' => $product_name,
          )
        )->execute();
      }
    }
    $url = Url::fromUri('internal:/teams/'. $org_name);
    $destination = $url->toString();
    $response = new RedirectResponse($destination);
    $response->send();
    \Drupal::messenger()->addStatus(t('Team product mapping successful.'));
  }
}