# IIIF Assemble

This small app WILL assemble and serve a IIIF Presentation API 3.0 manifest from a Fedora 3.8 object. The routing follows a pattern of  
`/assemble/manifest/{namepsace}/{id}` where `namespace` is a string and `id` is positive integer.

The example route of `/assemble/manifest/rfta/1` will correlate to **rfta:1**

@todo 
- all things IIIF
- tests
- cleanup?
- automating some of the testing steps

## Requirements

- PHP 7
- Composer
- Fedora 3.8 as a working a referencable installation
 
## Setup

For easiest testing and development, use with the custom Islandora vagrant, [utk_digital](https://github.com/utkdigitalinitiatives/utk_digital). Currently, you must use a feature branch **iiif_assemble** if using this way.
This application can be used independent the *utk_digital* vagrant and Islandora 7, though testing this way is more difficult and currently not outlined.

Note, using `utk_digital` will not require you to install this repository
separate as the latest version of `main` is automatically cloned and installed
upon `vagrant up` in *Step 4* of **Setup**.

### Get the utk_digital vagrant

1. `git clone git@github.com:utkdigitalinitiatives/utk_digital.git`
2. `cd utk_digital`
3. `git checkout iiif_assemble`
4. `vagrant up`

### Add the collection record

1. Browse to http://localhost:8000/collections/islandora/object/islandora%3Aroot
2. Click **Manage**
3. Click **Add an object to this Collection**
4. Insert Collection PID as *namespace:collection* and click **Next**
5. Insert Sample Title for Collection Title and click **Ingest** at the bottom

### Ingest a couple of sample records

```
vagrant ssh
cd $DRUPAL_HOME
drush en islandora_scg -y
drush iscgl --user=admin --quantity=5 --content_model=islandora:sp_basic_image --parent=collection:thing --namespace=thing
```

### Allow .htaccess overrides

```
vagrant ssh
sudo vim /etc/httpd/conf/httpd.conf
```

Find and modify `AllowOverride None` in

```
<Directory "/vhosts/digital/web">
    Options Indexes FollowSymLinks
    AllowOverride None
    Require all granted
</Directory>
```

to `AllowOverride All` so that it reads as:

```
<Directory "/vhosts/digital/web">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

Then restart the `httpd` service.

```
sudo systemctl restart httpd
```

**Should be good!**