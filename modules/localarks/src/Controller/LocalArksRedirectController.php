<?php

namespace Drupal\localarks\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller.
 */
class LocalArksRedirectController extends ControllerBase {

  /**
   * Redirect the user to the appropriate node.
   *
   * @param string $identifier
   *    The identifier to look up.
   *
   * @return object|string
   *    If an entity is found, redirect to it. If nothing found,
   *    throw a 404 response.
   */
  public function main() {
    $naan = \Drupal::routeMatch()->getRawParameter('naan');
    $idstring = \Drupal::routeMatch()->getRawParameter('idstring');
    // Minter prepends the redirector base URL when it mints the ARK, so we need to also prepend it here for the query.
    $config = \Drupal::config('localarks.settings');
    $localarks_redirector_host = $config->get('localarks_redirector_host');
    $ark = rtrim($localarks_redirector_host, '/') . '/ark:/' . $naan . '/' . $idstring;

    $persister_target_field = \Drupal::config('persistent_identifiers.settings')->get('persistent_identifiers_target_field');

    $node_query = \Drupal::entityQuery('node');
    $node_query->condition(trim($persister_target_field), trim($ark), '=');
    $results = $node_query->execute();
    if (count($results) == 0) {
      throw new NotFoundHttpException();
    }
    else {
      // For now, take the first node found (@todo: account for multiple results, maybe like Redirect From Identifier does it).
      $first_result = array_shift($results);
      $node_host = \Drupal::request()->getSchemeAndHttpHost();
      $node_url = $node_host . '/node/' . $first_result;
      $response = new RedirectResponse($node_url);
      $response->send();
      if ($config->get('localarks_log_redirects')) {
        \Drupal::logger('persistent_identifiers')->info(t("ARK @ark redirected to @node_url.", ['@ark' => $ark, '@node_url' => $node_url]));
      }
      return $response;
    }
  }

}