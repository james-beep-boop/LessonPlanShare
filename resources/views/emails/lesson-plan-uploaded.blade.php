<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Upload Confirmation</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 20px;">

    <h2 style="color: #4f46e5;">Lesson Plan Exchange</h2>

    <p>Hi {{ $recipientName }},</p>

    <p>Your lesson plan has been uploaded successfully. Here are the details:</p>

    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <tr>
            <td style="padding: 8px; font-weight: bold; color: #6b7280;">Filename:</td>
            <td style="padding: 8px; font-family: monospace;">{{ $canonicalFilename }}</td>
        </tr>
        <tr style="background: #f9fafb;">
            <td style="padding: 8px; font-weight: bold; color: #6b7280;">Class:</td>
            <td style="padding: 8px;">{{ $className }}</td>
        </tr>
        <tr>
            <td style="padding: 8px; font-weight: bold; color: #6b7280;">Lesson Day:</td>
            <td style="padding: 8px;">{{ $lessonDay }}</td>
        </tr>
        <tr style="background: #f9fafb;">
            <td style="padding: 8px; font-weight: bold; color: #6b7280;">Version:</td>
            <td style="padding: 8px;">{{ $semanticVersion }}</td>
        </tr>
    </table>

    <p>
        <a href="{{ $viewUrl }}"
           style="display: inline-block; padding: 10px 20px; background-color: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold;">
            View Your Lesson Plan
        </a>
    </p>

    <p style="margin-top: 30px; font-size: 12px; color: #9ca3af;">
        &mdash; Lesson Plan Exchange &bull; {{ config('app.url') }}
    </p>

</body>
</html>
