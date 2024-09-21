<?php
/**
 * Home Assistant transport
 *
 * @license GPL
 */

namespace LibreNMS\Alert\Transport;

use App\View\SimpleTemplate;
use LibreNMS\Alert\Transport;
use LibreNMS\Exceptions\AlertTransportDeliveryException;
use LibreNMS\Util\Http;

class Homeassistant extends Transport
{
    protected string $name = 'Home Assistant';

    public function deliverAlert(array $alert_data): bool
    {
        $entity_id = 'sensor.' . $this->config['entity_id'];
        $host = $this->config['host-url'] . "/api/states/" . $entity_id;
        $token = $this->config['hassio-token'];
        $data_template = '{
            "state": "{{ $timestamp }}",
            "attributes": {
                "hostname": "{{ $hostname }}",
                "sysName": "{{ $sysName }}",
                "sysDescr": "{{ $sysDescr }}",
                "os": "{{ $os }}",
                "type": "{{ $type }}",
                "ip": "{{ $ip }}",
                "hardware": "{{ $hardware }}",
                "version": "{{ $version }}",
                "uptime": "{{ $uptime }}",
                "uptime_short": "{{ $uptime_short }}",
                "description":"{{ $description }}",
                "title":"{{ $title }}",
                "msg":"{{ $msg }}"           
            }
        }';
        $request_headers = ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'];
        $request_body = SimpleTemplate::parse($data_template, $alert_data);


        $res = Http::client()
            ->withBody($request_body)->replaceHeaders($request_headers)
            ->acceptJson()
            ->post($host);   

        if ($res->successful()) {
            return true; // Delivery successful
        }

        throw new AlertTransportDeliveryException($alert_data, $res->status(), $res->body(), $alert_data['msg'], $request_body);
    }

    public static function configTemplate(): array
    {
        return [
            'config' => [
                [
                    'title' => 'Host URL',
                    'name' => 'host-url',
                    'descr' => 'The URL of your Home Assistant Host (http://<domain or ip>:<port>)',
                    'type' => 'text',
                ],
                [
                    'title' => 'Long-lived access token',
                    'name' => 'hassio-token',
                    'descr' => 'Obtain a token via your user profile page in Home Assistant',
                    'type' => 'password',
                ],
                [
                    'title' => 'Entity ID',
                    'name' => 'entity_id',
                    'descr' => 'Choose a unique name for the Entity',
                    'type' => 'text',
                ],
            ],
            'validation' => [
                'host-url' => 'required|url',
                'hassio-token' => 'required|string',
                'entity_id' => 'required|string',
            ],
        ];
    }
}
