# Nick's Home Automation Platform

This is a basic pile of PHP script to control the lights in my living room.

It runs as a webpage with some simple Javascript to download modes and make server requests.


## Configuration

Configured via a JSON configuration file that allows easy definition of a few basic concepts:

* WeMo switches require the IP address. Best to set these to static via your DHCP servermarator. WeMo switch nodes (though they have a text label in the JSON file) are controlled by the actual "friendly name" set in the app. I.E. they should match or if you end up changing the friendly name it will break things.
* PDU address specified separately, with each PDU node requiring a Unit number 1-8
* Mode definitions specify labels and states

## Other notes

* Modes are considered "active" and show up green whenever all states are satisfied.
* Currently state is cached only during script execution (multiple queries in short intervals return same result for perf; the WeMo switches are slow a.f.)
* Browser polls at 5 second intervals and displays updated states - if you change the configuration file, the states will reflect on all devices within 5 seconds. Also if you change a switch, all states will update commensurate with their active status within 5 seconds.

# Other Usage Details

I have local DNS set up so that http://home/ shows this server (with the FQDN home.509.local).

This also runs on a raspberry pi v3 with a tiny touch screen in "Kiosk Mode" so that the browser displays full screen. That way we can bolt it onto the apartment complex wall as a relatively innefficient power switch. You can also use any cheap phone and set it not to timeout.

I plan to add support for more geekery as time progresses :)
