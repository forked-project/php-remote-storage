# Introduction

This document describes how to run official releases for simple deployments.

These instructions were tested with Fedora 23 and Ubuntu 14.04 LTS.

We will be using the Apache web server with `mod_php`. This is the most common
deployment scenario, and most people will have some familiarity with this.

This document will assume you use the name `storage.local` for your server. If
you have your own domain name you can use that instead. These instructions 
configure the storage server on its own domain, as that is required for making
WebFinger work without hackery.

If you use `storage.local`, you can configure it in `/etc/hosts` on your own 
machine, not on the VM/server where you try to install php-remote-storage. If 
you use a name that resolves through DNS, you do not need to do this:

    1.2.3.4     storage.local

**NOTE**: if you choose your own domain name, replace all occurrences below 
with that domain name and do not forget to edit the web server configuration 
file accordingly!

Of course, dealing with TLS, one MUST verify the TLS configuration. Typically, 
I use both these services:

* [SSL Decoder](https://ssldecoder.org/)
* [SSL Labs](https://www.ssllabs.com/ssltest/)

# Dependencies

## Fedora

```bash
$ sudo dnf -y install httpd php php-pdo php-mbstring mod_ssl mod_xsendfile /usr/sbin/semanage
```

## Ubuntu/Debian

```bash
$ sudo apt install apache2 php php-mbstring php-curl php-intl libapache2-mod-xsendfile php-sqlite3
```

# Downloading

The releases can be downloaded from a remoteStorage server running this 
software.

Stable releases will also be hosted on GitHub, for now the test releases are 
only available from my remoteStorage server instance:

* from [GitHub](https://github.com/fkooman/php-remote-storage/releases)
* from [remoteStorage](https://storage.tuxed.net/fkooman/public/upload/php-remote-storage/releases.html) (self hosted);

# Installing

## Common

After downloading, extract the software in `/var/www`:

```bash
$ cd /var/www
$ sudo tar -xJf /path/to/php-remote-storage-VERSION.tar.xz
$ sudo mv php-remote-storage-VERSION php-remote-storage
$ cd php-remote-storage
$ sudo cp config/server.yaml.example config/server.yaml
```

Now add a user, by default no users are set up in the production template:

```bash
$ sudo php bin/add-user.php me p4ssw0rd
```

## Fedora

The instructions here are specific for Fedora.

Prepare the `data` directory for storing files, the database and template 
cache:

```bash
$ sudo mkdir data
$ sudo chown apache.apache data
$ sudo semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/php-remote-storage/data(/.*)?"
$ sudo restorecon -R data
```

Generate the SSL certificate:

```bash
$ sudo openssl genrsa -out /etc/pki/tls/private/storage.local.key 2048
$ sudo chmod 600 /etc/pki/tls/private/storage.local.key
$ sudo openssl req -subj "/CN=storage.local" -sha256 -new -x509 \
    -key /etc/pki/tls/private/storage.local.key \
    -out /etc/pki/tls/certs/storage.local.crt
```

Install the Apache configuration file:

```bash
$ sudo cp contrib/storage.local.conf.fedora /etc/httpd/conf.d/storage.local.conf
```

Enable the web server on boot and start it:

```bash
$ sudo systemctl enable httpd
$ sudo systemctl start httpd
```

You should now be able to go to 
[https://storage.local/](https://storage.local/), accept the self signed
certificate and login to the account page. To use with remoteStorage 
applications you can use the identity `me@storage.local`, or any other user
that you may have added above.

If you want to have your certificate signed by a CA you can also generate a 
CSR:

```bash
$ sudo openssl req -subj "/CN=storage.local" -sha256 -new \
    -key /etc/pki/tls/private/storage.local.key \
    -out storage.local.csr
```

Once you obtain the resulting certificate, overwrite the file 
`/etc/pki/tls/certs/storage.local.crt` with the new certificate, configure the
chain and restart the web server.

## Ubuntu/Debian

The instructions here are specific for Ubuntu/Debian.

Prepare the `data` directory for storing files, the database and template 
cache:

```bash
$ sudo mkdir data
$ sudo chown www-data.www-data data
```

Generate the SSL certificate:

```bash
$ sudo openssl genrsa -out /etc/ssl/private/storage.local.key 2048
$ sudo chmod 600 /etc/ssl/private/storage.local.key
$ sudo openssl req -subj "/CN=storage.local" -sha256 -new -x509 \
    -key /etc/ssl/private/storage.local.key \
    -out /etc/ssl/certs/storage.local.crt
```

Install the Apache configuration file:

```
$ sudo cp contrib/storage.local.conf.ubuntu /etc/apache2/sites-available/storage.local.conf
```

Enable some web server modules and enable the site:

```bash
$ sudo a2enmod rewrite
$ sudo a2enmod headers
$ sudo a2enmod ssl
$ sudo a2ensite default-ssl
$ sudo a2ensite storage.local
$ sudo service apache2 restart
```

You should now be able to go to 
[https://storage.local/](https://storage.local/), accept the self signed
certificate and login to the account page. To use with remoteStorage 
applications you can use the identity `me@storage.local`, or any other user
that you may have added above.

If you want to have your certificate signed by a CA you can also generate a 
CSR:

```bash
$ sudo openssl req -subj "/CN=storage.local" -sha256 -new \
    -key /etc/ssl/private/storage.local.key \
    -out storage.local.csr
```

Once you obtain the resulting certificate, overwrite the file 
`/etc/ssl/certs/storage.local.crt` with the new certificate, configure the
chain and restart the web server.
