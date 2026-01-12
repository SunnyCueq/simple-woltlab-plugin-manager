<?php

namespace wcf\http\attribute;

/**
 * Allows the user to be authed for the current request via an access-token.
 * A missing token will be ignored, an invalid token results in a throw of a IllegalLinkException.
 *
 * @author Marcel Werk
 * @copyright 2001-2024 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @since 6.1
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AllowAccessToken {}
