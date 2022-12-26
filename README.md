# Laravel Backend Api and Admin Panel
## About:
It serves as a Rest API and Admin Panel,users dashboard,Games,KYC.

## Environments Table

<table>
  <tr>
	  <th width="300px">Enviornment</th><th width="200px">Rest API</th><th width="200px">Admin Panel</th>
  </tr>
  <tr>
    <td>Test</td>
    <td>
      https://test-api.bluboygames.com/api/v1. 
    </td>
    <td>
      https://test-api.bluboygames.com/login. 
    </td>
  </tr>
  <tr>
   <td>Staging</td>
    <td>http://staging-api.bluboygames.com/api/v1</a></td>
    <td>http://staging-api.bluboygames.com/login</a></td>	  
  </tr>
  <tr>
   <td>Production</td>
    <td>http://api.bluboygames.com</a></td>
    <td>http://api.bluboygames.com/login</a></td>	  
  </tr>
  <tr>
 </table>


## Prerequisites
- Ubuntu 16.04
- php  ^7.3|^8.0
- laravel ^8.65
- Apache2
- Mysql 5.7|8

## Steps to Deploy on AWS

1. Login to your AWS account and create EC2 instance.
2. Create and set up EC2 with keep ports 80,443 and 3306 open to all and 22 ports.
If you don’t know how to do it, check this blog to get help!https://www.clickittech.com/aws/create-amazon-ec2-instance/
3. allocate elastic ip for this instance
4. Open hostenger and create A record then add your ip to that DNS

## Installation

Update your libraries.
Let’s update our libraries with the latest packages available.
```
sudo apt update
sudo apt upgrade
```

#### Install Apache2

```shell
sudo apt-get install apache2
sudo systemctl start apache2
```
#### PHP Installation & Configuration
``` shell
sudo add-apt-repository ppa:ondrej/php
sudo apt-get update
sudo apt-get install -y php7.4
```
Installing PHP 7.4 Extensions
```
sudo apt install php7.4-mysql php7.4-zip php7.4-mbstring php7.4-curl php7.4-xml php7.4-bcmath php7.4-gd
```
Check the PHP version.
````
php -v
````
Update in Php.ini file
```
sudo vim /etc/php/7.4/apache2/php.ini

#Update the following values in php.ini file

max_execution_time = 3000
max_input_time = 6000
memory_limit = 2048M
post_max_size = 256M
upload_max_filesize = 128M

$ sudo systemctl restart apache2
```

#### Mysql Setup
```
sudo wget https://dev.mysql.com/get/mysql-apt-config_0.8.15-1_all.deb
sudo dpkg -i mysql-apt-config_0.8.15-1_all.deb
```
 * Select MySQL server and cluster
 * Select MySQL 5.7 or 8
 * Click on Ok
 * Click on Ok
``` 
sudo apt-get update
sudo apt-get install mysql-server
mysql --version
sudo mysql
```
Setup Mysql root password
````
sudo mysql

SELECT user,authentication_string,plugin,host FROM mysql.user;
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'typeyourpassword';
FLUSH PRIVILEGES;

Exit
````
Creating Database

```
sudo mysql
CREATE DATABASE bluboygames
SHOW DATABASES
#here you can see all databases
```
Create User and give db access in mysql : 
```sql
CREATE USER 'bbgamesadmin'@'%' IDENTIFIED BY 'bluboy@123';
GRANT ALL PRIVILEGES ON bluboygames.* TO 'bbgamesadmin'@'%';
FLUSH PRIVILEGES;

SELECT user, host FROM mysql.user;
```

#### Composer Installation
```
cd ~
curl -sS https://getcomposer.org/installer -o composer-setup.php
HASH=`curl -sS https://composer.github.io/installer.sig`
php -r "if (hash_file('SHA384', 'composer-setup.php') === '$HASH') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    composer
```

#### Creating project directory
```
cd /var/www
mkdir bluboy
```

Giving the owner Permissions to project Directory
``` shell
sudo chown -R ubuntu:ubuntu /var/www/bluboy
sudo chmod -R 755 /var/www/bluboy
```

Clone the your project code 
```
git clone "repository url"
sudo cp -r backend-api/* bluboy
````

Creating the log file for logs
```shell
mkdir /var/www/bluboy/logs
chmod -R 777 /var/www/bluboy/logs
```
Giving the permissions to storage and bootstarp folders
```shell
sudo chmod -R 777 storage/
sudo chmod -R 777 bootstrap/
```
Upload .env file to laravel home directory  OR  copy and paste .env.example to .env
```shell
cp .env.example .env
```
Now set all configuration variables in .env like db credentials, app environment, etc
````
sudo vim .env
````
Database Credentials
```shell
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bluboygames
DB_USERNAME=root
DB_PASSWORD=<enter password>
```
#For Staging Server
```shell
APP_NAME=Bluboy
APP_ENV=local
APP_DEBUG=true
APP_URL=staging-api.bluboygames.com
```
#For Production Server
```shell
APP_NAME=Project name goes here
APP_ENV=production
APP_DEBUG=false
APP_URL=api.bluboygames.com
```
Go to Apache settings to add Virtual host 
```
cd /etc/apache2/sites-available/
sudo vim <domain/subdomain>.conf
<VirtualHost *:80>
        ServerName <domain/subdomain>                               

         DocumentRoot /var/www/<projectname>                        

        <Directory /var/www/<projectname>/public/ >                        
            Options Indexes FollowSymLinks
            AllowOverride All
            Require all granted
        </Directory>
        ErrorLog /var/www/<projectname>/logs/error.log               
		CustomLog /var/www/<projectname>/logs/access.log combined    
</VirtualHost>
````
Enable module rewrite in Apache
```
sudo a2enmod rewrite
sudo systemctl restart apache2
sudo apachectl -t
```
*Note:
Response should be OK for 'apachectl -t'* 
```shell
sudo a2ensite <subdomain/domain>.conf
sudo systemctl reload apache2
```
*Note:
domain/subdomain = staging.bluboygames.com
projectname = bluboy*

Goto your Project directory and Run the following
````
composer install 
php artisan key:generate
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan view:clear
php artisan view:cache
php artisan cache:clear
php artisan optimize:clear
composer dump-autoload
composer clear-cache
php artisan migrate
php artisan db:seed
php artisan db:seed
php artisan passport:install
php artisan passport:client --personal
``````
then enter "Bluboy"


- You want more details about above commands then got below link
https://dev.to/kenfai/laravel-artisan-cache-commands-explained-41e1


## Installing SSL Certificate
1. Goto SSLforfree.com and login
2. click on New certificacte
3. Enter your Domain name
4. Validate your ssl certification it means choose your validity 90 days
5. Based on your selection of a 90-Day SSL Certificate you will need the Basic Plan.
To create and validate your SSL Certificate
6. Select free plan
7. Email verification
8. Download Certificate
Your certificate is compatible with any type of web server. Download your certificate right away or make a selection below to get instructions and tutorials specific to your web server.
## Upload Certificate to Server 
Stpe1: After downloading your certificate, you should have a ZIP containing the following certificate files:
- certificate.crt
- ca_bundle.crt
- private.key

First, copy your certificate files to the directory where you keep your certificate and key files. Typically, this directory is /etc/ssl/ for your certificate.crt and ca_bundle.crt files, and /etc/ssl/private/ for your private.key file.

Step 2: Adjust Apache.config File and Add file path of your ssl certificates 
````
 sudo vim/etc/apache2/sites-enabled/your_site_name.
``````
`````
VirtualHost *:80>
        ServerName <domain/subdomain>
         DocumentRoot /var/www/<projectname>

        <Directory /var/www/<projectname>/public/>
            Options Indexes FollowSymLinks
            AllowOverride All
            Require all granted
        </Directory>
        ErrorLog /var/www/<projectname>/logs/error.log
		CustomLog /var/www/<projectname>/logs/access.log combined
</VirtualHost>
#adding ssl certificates file path
SSLEngine on
SSLCertificateFile /path/to/certificate.crt
SSLCertificateKeyFile /path/to/private.key
SSLCertificateChainFile /path/to/ca_bundle.crt
`````

As mentioned above, you will need to change the file names to match your certificate files and their location on the server:

SSLCertificateFile: This is your primary SSL certificate file (certificate.crt)
SSLCertificateChainFile: This is your CA-Bundle file (ca_bundle.crt)
SSLCertificateKeyFile: This is your private key file (private.key)
To verify whether or not your configuration works, you can run the following command:

Step 3: Test your config file
````
apachectl configtest
````
Next, save your Apache configuration file and restart your server using one of the commands below:

Step4:
````
apachectl stop
apachectl start
apachectl restart
````
In case something goes wrong along the way, please rest assured that you will be able to revert your Apache configuration file using the backup you have created earlier in the process. This way, you will be able to start over again.
Stpe5:
your SSL certificate is successfully installed on your Ubuntu Server and your domain is now live with HTTPS:// security.

Please click here, to check, if your certificate is installed properly or not.

(I) If the SSL is installed properly, you will able to see your certificate details.

(II) If it is not installed properly, we would recommend going back to Step 1 and retracing everything.





