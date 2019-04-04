<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/html4"
      xmlns:og="http://ogp.me/ns#"
      xmlns:fb="http://www.facebook.com/2008/fbml" lang="hu-HU">
<head>
	<title></title>
    <style type="text/css">
        * {
        }
        body, html {
            font-size: 13px;
            margin:0;
            padding: 0;
            font-family: 'Arial', sans-serif;
        }
        body {
            background: #f3f3f3;
        }

        header{
          padding: 10px 0;
          background: #ffffff;
        }

        footer{
          background: #4c4c4c;
        }

        a:link, a:visited {
            color:#F05F42;
        }
        .width {
            width: 800px;
             margin: 0 auto;
        }
        .pad {
            padding: 10px 0;
        }
        .bar {
            background: #9e9e9e;
        }
        .bar td {
            text-align: center;
        }
        .bar table {
            margin: 0 auto;
        }

        .bar a {
            font-size: 0.9rem;
            color: white;
            font-weight: bold;
            text-decoration: none;
            line-height: 1;
            padding: 10px 0;
            display: block;
        }
        .cdiv {
            height: 10px;
            background: #F05F42;
            display: block;
            position: relative;
        }
        .wb {
            background: #d9d9d9;
            font-size: 10px;
            color: #333333;
        }
        .radius {
            -webkit-border-radius: 8px;
            -moz-border-radius: 8px;
            border-radius: 8px;
        }
        .content-holder {
            background: #fff;
            color: #404040;
            padding: 25px;
        }

        .content-holder h1{
          color: black;
          margin: 0 0 25px 0;
        }

        .footer {
            text-align: center;
            font-size: 12px !important;
            color: #ffffff !important;
        }
        .footer a {
          color: white;
        }
        .footer .row {
            margin: 5px 0;
        }
        .footer tr td {
            text-align: center;
            border-right: 1px solid #ffffff;
            font-size: 12px !important;
            color: #ffffff !important;
        }
         .footer tr td:last-child {
            border-right: none;
        }

         table.if strong {
            color: #444444;
        }
        table.if td{
          padding: 5px;
        }
        table.if th{
          background: #fafafa;
          text-align: left;
          padding: 5px;
        }
        table.if,
        table.if td,
        table.if th {
            border: 1px solid #d7d7d7;
            border-collapse: collapse;
        }
        table.preview td{
          padding: 0;
        }
        table.preview td img {
          width: 100%;
          max-width: 100%;
        }

        table.smaller-tbl td {
          font-size: 0.9rem;
        }

        table.if tbody td a {
            font-weight: bold;
        }
        .clr{
          clear: both;
        }

        @media all and (max-width: 800px){
          .width{
            width: 100%;
          }
        }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
</head>
<body>

<header>
  <div class="width">
      <table width="100%" border="0" style="border:none;">
          <tr>
              <tbody>
                  <tr>
                      <td  width="125" style="text-align:left; vertical-align:middle;">
                          <img src="<?=IMGDOMAIN?>src/images/moza_logo_hu.svg" alt="<?=$settings['page_title']?>" style="width:auto !important; height:40px;">
                      </td>
                      <td style="text-align:right; vertical-align:middle; font-size:1rem;" >
                          <div class="relax"><?=$settings['page_description']?></div>
                      </td>
                  </tr>
              </tbody>
          </tr>
      </table>
  </div>
</header>

<div class="bar">
    <div class="width">
        <table border="0" style="border:none; width: 100%;">
            <tr>
                <tbody>
                    <tr>
                        <td style="width: 20%;"><a href="<?=$settings['blog_url']?>">moza.hu</a></td>
                        <td style="width: 25%;"><a href="<?=$settings['blog_url']?>/wp-content/uploads/2019/03/aszf.pdf">Általános Szerződési Feltételek</a></td>
                        <td style="width: 20%;"><a href="<?=$settings['blog_url']?>/dokumentumtar">Dokumentumok</a></td>
                        <td style="width: 20%;"><a href="<?=$settings['blog_url']?>/kapcsolat">Kapcsolat</a></td>
                    </tr>
                </tbody>
            </tr>
        </table>
    </div>
</div>

<div class="width">
<div class="in-content">
    <div class="content-holder">
