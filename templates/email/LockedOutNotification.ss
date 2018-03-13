<% include EmailHeader %>

<h1 style="font-size: 28px;line-height: 36px;margin: 0 0 24px;padding: 0;color: #3c4c76;font-family: Arial, sans-serif;">$Title</h1>

<p>
    <%t LockedOutNotification.intro 'A member has been locked out after {attempts} login attempts.' attempts=Member.FailedLoginCount %>
</p>

<table width="100%" border="0" cellspacing="0" cellpadding="0" style="font-family: Arial, sans-serif; font-size:12px">
    <tr>
        <td style="padding: 5px 10px 5px 0; font-weight: bold;">Member:</td>
        <td style="padding: 5px 0;">$Member.Name (ID: $Member.ID)</td>
    </tr>
    <tr>
        <td style="padding: 5px 10px 5px 0; font-weight: bold;">Email:</td>
        <td style="padding: 5px 0;">$Member.Email</td>
    </tr>
    <tr>
        <td style="padding: 5px 10px 5px 0; font-weight: bold;">IP:</td>
        <td style="padding: 5px 0;">$Member.LastFailedLoginAttempt.IP</td>
    </tr>
</table>

<% include EmailFooter %>