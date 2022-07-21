<?xml version="1.0"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml"/>
<xsl:template match="/MeLeeCMS">
<xsl:apply-templates select="content[not(@hidden) and @id!='branding' and @id!='nav' and not(@notification)]"/>
</xsl:template>
</xsl:stylesheet>
