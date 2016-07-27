@extends('layouts.app')

@section('footer')

    <h1>Upload the document to continue</h1>
    <form method="post" action="upload" enctype="multipart/form-data">
        <input type="file" name="fileToUpload" id="fileToUpload">
        <input type="submit" name="submit">upload the document</input>
    </form>


@stop