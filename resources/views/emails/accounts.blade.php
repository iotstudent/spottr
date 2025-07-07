
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width" />
    <title>JS Bin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter&family=Space+Grotesk:wght@300;400;500;600;700&display=swap"rel="stylesheet"/>
    <style>
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }

      body {
        font-family: "Inter", sans-serif;
      }

      .ctr {
        background: #fbfaff;
        padding: 24px;
      }

      .email {
        max-width: 640px;
        margin: auto;
      }

      .email p {
        color: #333;
      }

      .email .header {
        padding: 24px;
        background-color: #000;
      }

      .email .body {
        padding: 24px;
        background-color: #fff;
      }

      .email .body p a.app-link {
        word-wrap: break-word;
        overflow-wrap: break-word;
        color: #538dff;
      }

      .email .body p {
        margin-bottom: 16px;
        color: #333;
      }

      .email .body a.contact {
        color: #333;
      }

      .email .footer {
        padding: 32px;
        background-color: #f7f9fc;
      }

      .email .footer p {
        font-size: 14px;
      }

      .email .footer .unsub a {
        color: #333;
      }

      .email .footer .socials {
        margin-top: 48px;
      }

      .email table {
        width: 100%;
      }

      .email table td.links {
        text-align: right;
      }

      @media screen and (min-width: 640px) {
        .ctr {
          padding: 68px;
        }

        .email .header {
          padding: 24px 32px;
        }

        .email .body {
          padding: 32px;
        }
      }
    </style>
  </head>
  <body>
    <div class="ctr">
      <div class="email">
        <div class="header">
          <img
            src=""
            alt="logo"
            width="117"
          />
        </div>

        <div class="body">
          <p>
           Dear {{$name}}
          </p>
          <p>
            {{$text}}
          </p>

        </div>

        <div class="footer">
          <p class="unsub">
            You are receiving this kind of email because it is important for the functionality of your account.
          </p>

          {{-- <div class="socials">
            <table>
                <tr>
                  <td>
                    <img
                      src=""
                      alt=" logo"
                      width="87"
                    />
                  </td>
                  <td class="links">
                    <a
                      href=""
                      target="_blank"
                      style="margin-right: 24px"
                      ><img
                        src="https://s3.eu-west-2.amazonaws.com/files.aitechma.com/app/twitter_i.png"
                        alt="Twitter icon"
                        width="24"
                    /></a>
                    <a
                      href=""
                      target="_blank"
                      style="margin-right: 24px"
                      ><img
                        src=""
                        alt="Facebook icon"
                        width="24"
                    /></a>
                    <a
                      href=""
                      target="_blank"
                      ><img
                        src="https://s3.eu-west-2.amazonaws.com/files.aitechma.com/app/linkedin_i.png"
                        alt="LinkedIn icon"
                        width="24"
                    /></a>
                  </td>
                </tr>
              </table>
          </div> --}}
        </div>
      </div>
    </div>
  </body>
</html>
