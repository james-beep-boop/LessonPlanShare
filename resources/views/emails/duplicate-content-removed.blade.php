<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Duplicate Content Notification</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 20px;">

    <h2 style="color: #4f46e5;">Lesson Plan Exchange</h2>

    <p>Hi {{ $recipientName }},</p>

    <p>
        We detected that a lesson plan you uploaded has identical content to an earlier upload by another teacher.
        To keep the library clean, we have removed the duplicate.
    </p>

    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <tr>
            <td style="padding: 8px; font-weight: bold; color: #6b7280;">Your upload (removed):</td>
            <td style="padding: 8px;">{{ $deletedPlanName }}</td>
        </tr>
        <tr style="background: #f9fafb;">
            <td style="padding: 8px; font-weight: bold; color: #6b7280;">Earlier upload (kept):</td>
            <td style="padding: 8px;">{{ $keptPlanName }} by {{ $keptAuthorName }}</td>
        </tr>
    </table>

    <p>
        If you believe this was a mistake or you have questions, please contact the site administrator.
    </p>

    <p style="margin-top: 30px; font-size: 12px; color: #9ca3af;">
        &mdash; Lesson Plan Exchange &bull; {{ config('app.url') }}
    </p>

</body>
</html>
