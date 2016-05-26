# SETUP

## INSTALL (Amazon Linux)

```
$ sudo rpm -Uvh ftp://ftp.scientificlinux.org/linux/scientific/6.4/x86_64/updates/fastbugs/scl-utils-20120927-8.el6.x86_64.rpm
$ sudo rpm -Uvh http://rpms.famillecollet.com/enterprise/remi-release-6.rpm
$ sudo yum install -y nginx php70 php70-php-fpm php70-php-pdo php70-php-mbstring
$ sudo yum install -y git

$ sudo chkconfig nginx on
$ sudo chkconfig php70-php-fpm on

$ sudo mkdir /var/www/
$ sudo chown ec2-user:ec2-user /var/www/

$ sudo ln -s /usr/bin/php70 /usr/bin/php

cd /var/www/
git clone https://github.com/inouet/aws-support-viewer.git
cd aws-support-viewer
curl -sS https://getcomposer.org/installer | php
./composer.phar install
```



## CONFIGURE

### nginx

/etc/nginx/nginx.conf

```
    server {
        listen 80;
        server_name localhost;
        index index.php;
        error_log /var/log/nginx/error.log;
        access_log /var/log/nginx/access.log;
        root /var/www/aws-support-viewer/public;

        location / {
            try_files $uri $uri/ /index.php$is_args$args;
        }

        location ~ \.php {
            try_files $uri =404;
            fastcgi_split_path_info ^(.+\.php)(/.+)$;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param SCRIPT_NAME $fastcgi_script_name;
            fastcgi_index index.php;
            fastcgi_pass 127.0.0.1:9000;
        }
    }
```

Public Dir: /var/www/aws-support-viewer/public/

```
$ sudo service nginx start
$ sudo service php70-php-fpm start
```

### AWS 


#### 1. Create IAM User

```
$ aws iam create-user --user-name <user_name>
{
    "User": {
        "UserName": "<user_name>",
        "Path": "/",
        "CreateDate": "2016-05-24T06:21:04.872Z",
        "UserId": "<user_id>",
        "Arn": "arn:aws:iam::123456789012:user/<user_name>"
    }
}
```

#### 2. Create AccessKey

````
$ aws iam create-access-key --user-name <user_name>
{
    "AccessKey": {
        "UserName": "<user_name>",
        "Status": "Active",
        "CreateDate": "2016-05-24T06:33:37.717Z",
        "SecretAccessKey": "<accesskey>",
        "AccessKeyId": "<secretkey>"
    }
}
````

#### 3. Attatch Policy

```
$ aws iam attach-user-policy --user-name <user_name> --policy-arn "arn:aws:iam::aws:policy/AWSSupportAccess"
```

#### 4. Configure AWS CLI

```
$ aws configure --profile <profile_name>
  AWS Access Key ID [None]: <accesskey>
  AWS Secret Access Key [None]: <secretkey>
  Default region name [None]: 
  Default output format [None]:
```

### aws-support-viewer

Initial import (fetch all cases)

```
$ php /var/www/aws-support-viewer/bin/fetch_cases.php --status all --profile <profile_name>
```

Initial import (import cases)

```
$ php /var/www/aws-support-viewer/bin/import_cases.php
```

## SET crontab

```
 # Dayly
 30 0 * * * /usr/bin/php /var/www/aws-support-viewer/bin/fetch_cases.php --status all --profile <profile_name>

 # Hourly
 16 * * * * /usr/bin/php  /var/www/aws-support-viewer/bin/fetch_cases.php --status open --profile <profile_name>
 17 * * * * /usr/bin/php  /var/www/aws-support-viewer/bin/import_cases.php

```