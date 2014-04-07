This code was developed at the Gygi proteomics lab at Harvard Medical by George Schneeloch.

Note: this was recently copied from our own source repository, it's not yet ready to use
out of the box.

DataForm
========

This is a PHP 5.2 library which renders HTML tables that are refreshable via AJAX. They are designed to have a loose connection with a data source,
allowing the user to implement interfaces to format data and customize the view. (Callbacks are a PHP 5.3 feature and therefore weren't available
for use.)

Steps:
 - Create a DataForm object. Tell it which columns to display, how to format the data for display, which columns
allow searching and sorting, and whether to paginate the data. See the `examples/` folder for code snippets.
 - Connect the data source. The keys of each column match up with the keys of the data source. The data source
may be rows from an iterator or an array.
 - Display the form as HTML. When the user makes a change to the form, an AJAX request is sent and an updated
copy of the form is returned.

It has the following features:
 - UI for sorting, filtering and pagination
 - Automatic editing of SQL for these changes using the PHP-SQL-Parser library. You may also handle this part 
 - Server side formatting of data via PHP interfaces
 - Preservation of data during refreshes and from different pages. Items selected on previous pages
are seamlessly passed with the request when the form is submitted.
 - Validation of data
 - Error reporting in a flash message

## Usage

See `examples/`. This includes code specific to our lab, but it should be general enough to get started.

## Requirements
 - PHP 5.2 or later
 - PHP-SQL-Parser - https://code.google.com/p/php-sql-parser/
 - jQuery

## Caveats

 - This can be somewhat heavyweight with large forms. It's designed to scale well with large amounts of data
but this library was designed for internal use and isn't optimized for bandwidth or performance.
 - The form makes liberal use of hidden fields to preserve state, which may run into size limits with GET
requests.
 - This library has been designed to be restrictive in what it accepts, to catch errors earlier. Feel free to
relax these restrictions if they don't make sense for your use case.

## License
BSD 3 clause
