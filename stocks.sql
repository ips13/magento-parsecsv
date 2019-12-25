
-- --------------------------------------------------------

--
-- Table structure for table `csvfiles`
--
CREATE TABLE IF NOT EXISTS `csvfiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stockfiles` text NOT NULL,
  `catalogfiles` text NOT NULL,
  `store` varchar(20) NOT NULL,
  `added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;



--
-- Table structure for table `csv_catalog`
--

CREATE TABLE IF NOT EXISTS `csv_catalog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(20) NOT NULL,
  `name` text NOT NULL,
  `product_type` text NOT NULL,
  `design` text NOT NULL,
  `categories` text NOT NULL,
  `subcategories` text NOT NULL,
  `description` text NOT NULL,
  `images` text NOT NULL,
  `virtual` text NOT NULL,
  `configurable_variations` text NOT NULL,
  `store` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3207 


--
-- Table structure for table `csv_stocks`
--

CREATE TABLE IF NOT EXISTS `csv_stocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(20) NOT NULL,
  `qty` int(11) NOT NULL,
  `store` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=23345 ;