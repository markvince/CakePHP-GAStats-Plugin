CREATE DATABASE IF NOT EXISTS `gastats`;
Use `gastats`;
CREATE TABLE IF NOT EXISTS `gastats_ads` (
  `id` char(36) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `ad_stat_type` varchar(64) NOT NULL,
  `location` varchar(64) NOT NULL,
  `corp_id` int(11) NOT NULL DEFAULT '0',
  `ad_id` int(11) NOT NULL DEFAULT '0',
  `ad_slot` varchar(64) NOT NULL,
  `value` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gastats_countries`
--

CREATE TABLE IF NOT EXISTS `gastats_countries` (
  `id` char(36) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `country` varchar(64) NOT NULL,
  `visits` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gastats_raws`
--

CREATE TABLE IF NOT EXISTS `gastats_raws` (
  `id` char(36) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `key` varchar(64) NOT NULL,
  `value` varchar(64) NOT NULL,
  `stat_type` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gastats_webchannels`
--

CREATE TABLE IF NOT EXISTS `gastats_webchannels` (
  `id` char(36) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `corp_id` int(11) NOT NULL DEFAULT '0',
  `channel` varchar(64) NOT NULL,
  `metric` varchar(64) NOT NULL,
  `value` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gastats_webstats`
--

CREATE TABLE IF NOT EXISTS `gastats_webstats` (
  `id` char(36) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `metric` varchar(64) NOT NULL,
  `value` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `gastats_videos`
--

CREATE TABLE IF NOT EXISTS `gastats_videos` (
  `id` char(36) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `corp_id` int(11) NOT NULL,
  `video_id` int(11) NOT NULL,
  `details` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for table `gastats_videos`
--
ALTER TABLE `gastats_videos`
  ADD PRIMARY KEY (`id`);

