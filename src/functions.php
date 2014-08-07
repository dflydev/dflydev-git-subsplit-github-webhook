<?php

/*
 * This file is part of the Git Subsplit GitHub WebHook package.
 *
 * (c) Beau Simensen <beau@dflydev.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Match IPv4 address with list of allowed IPv4 (also in CIDR notation) addresses
 *
 * @author Piotr Minkina <projekty@piotrminkina.pl>
 *
 * @param string $ipAddress IPv4 address, e.g. 127.0.0.1
 * @param array $allowedIps List of allowed IPv4 addresses, e.g. 127.0.0.0/8, 192.168.1.0/24, 8.8.8.8
 * @return bool
 */
function checkIPv4Address($ipAddress, array $allowedIps)
{
    $ipAddress = inet_pton($ipAddress);

    foreach ($allowedIps as $allowedIp) {
        $subNetSize = 32;

        if (false !== strpos($allowedIp, '/')) {
            list($allowedIp, $subNetSize) = explode('/', $allowedIp, 2);
        }
        $allowedIp = inet_pton($allowedIp);
        $addressSize = strlen($allowedIp);

        if ($allowedIp === $ipAddress) {
            return true;
        } elseif (strlen($ipAddress) !== $addressSize) {
            continue;
        }

        $netMask = str_repeat('1', $subNetSize);
        $netMask = str_pad($netMask, $addressSize * 8, '0');
        $netMask = str_split($netMask, 8);
        $netMask = array_map('bindec', $netMask);
        $netMask = array_map('chr', $netMask);
        $netMask = join('', $netMask);

        if (($ipAddress & $netMask) === ($allowedIp & $netMask)) {
            return true;
        }
    }

    return false;
}
