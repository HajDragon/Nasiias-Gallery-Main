<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>New gallery contact message</title></head>
<body>
    <h1>New contact message</h1>
    <p><strong>From:</strong> {{ $senderName }} ({{ $senderEmail }})</p>
    <div style="white-space: pre-wrap">{{ $messageBody }}</div>
    <p>Reply to this email to contact the sender.</p>
</body>
</html>
