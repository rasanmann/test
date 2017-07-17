<?php

namespace Drupal\yqb_api\Access;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessResult;

/**
 * Access protection against CSRF attacks.
 */
class CsrfRequestHeaderAccessCheck extends \Drupal\Core\Access\CsrfRequestHeaderAccessCheck {

    /**
     * Checks access.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     * @param \Drupal\Core\Session\AccountInterface $account
     *   The currently logged in account.
     *
     * @return \Drupal\Core\Access\AccessResultInterface
     *   The access result.
     */
    public function access(Request $request = null, AccountInterface $account = null) {
        // Check if request has key header, request handler will handle the rest
        if ($request && \Drupal::request()->headers->has('X-Key')) {
            return AccessResult::allowed()->setCacheMaxAge(0);
        }

        return parent::access($request, $account);
    }
}
