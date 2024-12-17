<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        .input_width{
            width: 100%;
        }
    </style>
</head>
<body
    style="background-color: gray ;display: flex;align-items:center;justify-content: center;width: 100%;height: 600px">
<div
    style="display: flex;padding: 32px; justify-content: center;align-content: center; width: 60%;height: 100%;background-color: white">

    <div style="">
        <div>
        <div>
            @if(session('incorrectBuilder'))
                <div class="alert alert-primary">{{session('incorrectBuilder')}}
                </div>
            @elseif(session('incorrectCompany'))
                <div class="alert alert-warning">{{session('incorrectCompany')}}
                </div>
            @elseif(session('fail'))
                <div class="alert alert-danger">{{session('fail')}}
                </div>
            @endif
        </div>
        </div>
        <div>
            <h1>Insert data</h1>
            <p>Please upload you excel file</p>
        </div>
        <div>

            <div>
                <form method="post" action="{{route('import.file')}}" enctype="multipart/form-data">
                    @csrf

                    <div style="display: flex;flex-direction: column;align-content: space-evenly">

                        <label for="">Select Today order file :</label>
                        <div style="" class="input-group mb-3">

                            <input required name="builder" id="builder" type="file" class="form-control" placeholder="Recipient's username"
                              aria-label="Recipient's username" aria-describedby="basic-addon2">
                        </div>
                    <label for="">Select Infrastructure type file :</label>
                    <div class="input-group mb-3 input_width">
                        <input required name="cfo" id="cfo" type="file" class="form-control" placeholder="Recipient's username"
                               aria-label="Recipient's username" aria-describedby="basic-addon2">
                    </div>

                    </div>
                    <label>Select Company :</label>
                    <div>
                        <select required class="input_width " style="border-radius: 8px;border-color: #cbd5e0 ; padding: 6px 12px;"  id="cars" name="company">
                            <option value="tct">TCT FTTH</option>
                            <option value="telecom">Telecom Cambodia</option>
                            <option value="adi">ADI</option>
                            <option value="cfocn">CFOCN</option>
                        </select>

                    </div>
                    <div>
                        <label>Status :</label>
                        <div style="padding-bottom: 21px;display: flex;justify-content: space-evenly">
                            <div>
                        <input type="radio" value="Active" name="status" id="active_status">
                        <label>Active</label>
                            </div>
                            <div>
                            <input type="radio" value="Terminated" name="status" id="terminated_status">
                            <label>Terminated</label>
                            </div>
                        </div>
                    </div>

{{--                    <label for="">Select Detail file :</label>--}}
{{--                    <div class="input-group mb-3">--}}
{{--                        <input required name="detailFile" id="detailFile" type="file" class="form-control" placeholder="Recipient's username"--}}
{{--                               aria-label="Recipient's username" aria-describedby="basic-addon2">--}}
{{--                    </div>--}}
                    <div style="display: flex;justify-content: space-around" >
                        <button type="submit"
{{--                                data-bs-toggle="modal" data-bs-target="#exampleModal" --}}
                                class="btn btn-success" style="margin-bottom: 34px">Submit file
                        </button>
{{--                        <button type="button" class="btn btn-danger"  style="margin-bottom: 34px" >--}}
{{--                            <a href="{{route('export.file')}}" style="text-decoration: none; color: white;">Download</a>--}}
{{--                        </button>--}}
                    </div>

                </form>
            </div>
        </div>

    </div>
    <div>


    </div>
</div>
{{--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"--}}
{{--        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"--}}
{{--        crossorigin="anonymous"></script>--}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

</body>
</html>
