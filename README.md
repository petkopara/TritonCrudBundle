# CrudGeneratorBundle
Symfony3 CRUD generator bundle with pagination, filtering, Twitter bootstrap 3.3.6 markup and many other features.
It's Simple to use and fully customizable.

Designed to bring back the functionality of the old Symfony 1.4 admin generator, but extending from [SensioGeneratorBundle](https://github.com/sensio/SensioGeneratorBundle) with additional options and features.

[![Build Status](https://travis-ci.org/petkopara/PetkoparaCrudGeneratorBundle.svg?branch=master)](https://travis-ci.org/petkopara/PetkoparaCrudGeneratorBundle)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/7d24085a-9a27-4607-adf5-efe1bb39f62b/mini.png)](https://insight.sensiolabs.com/projects/7d24085a-9a27-4607-adf5-efe1bb39f62b)
[![Latest Stable](https://img.shields.io/packagist/v/petkopara/crud-generator-bundle.svg?maxAge=2592000?style=flat-square)](https://packagist.org/packages/petkopara/crud-generator-bundle)
[![Code Coverage](https://scrutinizer-ci.com/g/petkopara/PetkoparaCrudGeneratorBundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/petkopara/PetkoparaCrudGeneratorBundle/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/petkopara/PetkoparaCrudGeneratorBundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/petkopara/PetkoparaCrudGeneratorBundle/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/petkopara/crud-generator-bundle.svg?maxAge=2592000?style=flat-square)](https://packagist.org/packages/petkopara/crud-generator-bundle)

## Features
* Pagination - using PagerFanta
* Filtering (With single multi search input or form)
* Doctrine association mapping support for Many-to-One, One-to-One and Many-to-Many (if the relation is the owning side) in forms and filters
* Sorting 
* Items per page
* Bulk actions(delete) on multiple rows
* Delete from index
* Set your base template in the generated views.
* Possibility to set the save path for all the generated files (by default in app/Resources).
* Possiblity to not generate show code.
* Most of the features are optional and you can generate the CRUD very flexible depending on your needs.

## Screenshots

### Multi Search Filter
![Screenshot](https://raw.github.com/petkopara/PetkoparaCrudGeneratorBundle/master/screenshot_multi.png "Screenshot Multi Search")
### Form Filter 
![Screenshot](https://raw.github.com/petkopara/PetkoparaCrudGeneratorBundle/master/screenshot_form.png "Screenshot Form Filter")

## Installation
This bundle is compatible with Symfony 2.8/3.0 or higher.

### Using composer

#### Symfony >= 2.8 

    composer require petkopara/crud-generator-bundle

Register the CRUD and filter bundles in your `AppKernel.php`:

    new Lexik\Bundle\FormFilterBundle\LexikFormFilterBundle(),
    new Petkopara\MultiSearchBundle\PetkoparaMultiSearchBundle(),
    new Petkopara\CrudGeneratorBundle\PetkoparaCrudGeneratorBundle(),

Install the assets.
```sh
php bin/console assets:install --symlink
```

For the bootstrap theme of the forms, add this to your `app/config/config.yml`
```yaml
twig:
    form_themes:
	- 'bootstrap_3_layout.html.twig' 

```

Optionally if you are using your own base template, be sure that you include the javascript file of the bundle in it.

    <script src="{{ asset('bundles/petkoparacrudgenerator/js/petkopara-crud-generator.js') }}"></script>

## Dependencies

This bundle extends [SensioGeneratorBundle](https://github.com/sensio/SensioGeneratorBundle). 
Pagination with [PagerFanta](https://github.com/whiteoctober/Pagerfanta/) . 
For the filtering is used [PetkoparaMutiSearchBundle]( https://github.com/petkopara/PetkoparaMultiSearchBundle) and [LexikFormFilterBundle](https://github.com/lexik/LexikFormFilterBundle).

## Usage

Use the following command from console:
```sh
php bin/console petkopara:generate:crud
```
And follow the wizard steps.

### Available new options
The default behavior of the bundle is to generate full featured crud, but you can customize what to be generated or not. 
The bundle adds few new parameters compared to the doctrine crud generator, to control all of the new features.

* `--filter-type` - Which filter type to use. There is three options:
  * input - To use Multi Search input.
  * form - To use Lexik form filter.
  * none - Will not generate any filter code.

* `--template` - The base template name, which the views will override. For example set it to `--template=base.html.twig` to extends your base template.(by default PetkoparaCrudGeneratorBundle::base.html.twig).

* `--without-write` - The default behavior of the bundle is to generate write code, so for that `--with-write` option is transformed to this.

* `--without-show` - Many times you don't need show code, for that this option is introduced. 

* `--without-sorting` - To not generate sorting code.

* `--without-page-size` - To not generate items per page code.

* `--without-bulk` - To not generate bulk actions code.

* `--bundle-views` - Whether to store the view files in the bundles dir. By default the vies are stored in _app/Resources/views/_ .


Don't forget, that this is a just crud generator and you are free to change everything generated from this bundle. 

## Author

Petko Petkov - petkopara at gmail dot com

## License

CrudGeneratorBundle is licensed under the MIT License.
