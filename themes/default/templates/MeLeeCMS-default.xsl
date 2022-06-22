<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<xsl:template match="/MeLeeCMS">
<xsl:text disable-output-escaping="yes">&lt;!DOCTYPE html&gt;</xsl:text>
<html>
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
<title><xsl:value-of select="title"/></title>
<xsl:for-each select="css[href!='']">
	<xsl:element name="link">
		<xsl:attribute name="type">text/css</xsl:attribute>
		<xsl:attribute name="rel">stylesheet</xsl:attribute>
		<xsl:attribute name="href"><xsl:value-of select="href"/></xsl:attribute>
		<xsl:if test="integrity"><xsl:attribute name="integrity"><xsl:value-of select="integrity"/></xsl:attribute></xsl:if>
		<xsl:if test="crossorigin"><xsl:attribute name="crossorigin"><xsl:value-of select="crossorigin"/></xsl:attribute></xsl:if>
	</xsl:element>
</xsl:for-each>
<xsl:for-each select="css[code!='']">
	<style><xsl:value-of select="code"/></style>
</xsl:for-each>
</head>
<body>
<ul>
<xsl:for-each select="content[@id='nav']">
	<li><xsl:value-of select="."/></li>
</xsl:for-each>
</ul>

<xsl:for-each select="content[@id!='nav' and @class!='Data']">
	<xsl:value-of select="."/>
</xsl:for-each>

<xsl:for-each select="js">
	<xsl:element name="script">
		<xsl:attribute name="type">text/javascript</xsl:attribute>
		<xsl:if test="src"><xsl:attribute name="src"><xsl:value-of select="src"/></xsl:attribute></xsl:if>
		<xsl:if test="integrity"><xsl:attribute name="integrity"><xsl:value-of select="integrity"/></xsl:attribute></xsl:if>
		<xsl:if test="crossorigin"><xsl:attribute name="crossorigin"><xsl:value-of select="crossorigin"/></xsl:attribute></xsl:if>
		<xsl:if test="code"><xsl:value-of select="code"/></xsl:if>
	</xsl:element>
</xsl:for-each>
</body>
</html>
</xsl:template>

</xsl:stylesheet>
