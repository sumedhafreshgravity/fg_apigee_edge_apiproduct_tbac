<?php
/**
 * @file
 * Contains \Drupal\fg_apigee_edge_apiproduct_tbac\Controller\MappingController.
 */
namespace Drupal\base\Controller;
namespace Drupal\fg_apigee_edge_apiproduct_tbac\Controller;

use Drupal\Core\Controller\ControllerBase;

use \Drupal\Core\Url;
use  \Symfony\Component\HttpFoundation\RedirectResponse;



/**
 * Contains the BaseController controller.
 * used to redirect to product mapping page on teams page
 */
class MappingController extends ControllerBase
{
   /**
    * Function redirecttomapping
    * Redirectis the flow to to product mapping page
    * @author Sumedha
    * @param [type] $team teamId
    * @return void
    */
   public function redirecttomapping($team) {
      $user = \Drupal::currentUser();

      $url = Url::fromUri('internal:/simple-custom-form');
      $link_options = array(
         'query' => array(
            'teamrole' =>$team,
         )
      );
      $url->setOptions($link_options);
      $destination = $url->toString();

      if($user->hasPermission('administer organizations')) {
         $response = new RedirectResponse($destination);
         $response->send();
      }else {
         \Drupal::messenger()->addStatus(t('Permission Denied.Contact Admin.'));

      }

   }

}
