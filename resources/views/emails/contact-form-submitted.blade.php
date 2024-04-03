<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form Submission</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
        }

        p {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .field {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Contact Form Submission</h1>
    <p>
        <span class="field">Name:</span> {{ $data['name'] }}
    </p>
    <p>
        <span class="field">Email:</span> {{ $data['email'] }}
    </p>
    <p>
        <span class="field">Message:</span>
    </p>
    <p>{{ $data['message'] }}</p>
</body>
</html>
